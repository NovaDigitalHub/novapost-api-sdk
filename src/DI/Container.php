<?php

/**
 * Copyright (C) 2025 NovaDigital
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace NovaDigital\NovaPost\DI;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\HttpFactory;
use NovaDigital\NovaPost\Exception\ApiException;
use NovaDigital\NovaPost\Exception\ContainerException;
use NovaDigital\NovaPost\Exception\ServiceNotFoundException;
use NovaDigital\NovaPost\Http\AuthClient;
use NovaDigital\NovaPost\Http\Client;
use NovaDigital\NovaPost\Http\HttpResponseValidator;
use NovaDigital\NovaPost\Http\ResponseValidatorInterface;
use NovaDigital\NovaPost\Http\RetryHandlerInterface;
use NovaDigital\NovaPost\Http\TokenRetryHandler;
use NovaDigital\NovaPost\JwtTokenProvider;
use NovaDigital\NovaPost\NovaPostApi;
use NovaDigital\NovaPost\Storage\FileJwtTokenStorage;
use NovaDigital\NovaPost\Storage\JwtTokenStorageInterface;
use NovaDigital\NovaPost\TokenProviderInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

class Container implements ContainerInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $config;

    /**
     * @var array<string, callable>
     */
    private array $factories = [];

    /**
     * @var array<string, mixed>
     */
    private array $resolved = [];

    /**
     * @var array<string, bool>
     */
    private array $resolving = [];

    /**
     * @var array<string, bool>
     */
    private array $singletons = [];

    /**
     * @param array<string, mixed> $config
     * @throws ApiException
     */
    public function __construct(array $config = [])
    {
        $this->config = $this->validateAndNormalizeConfig($config);
        $this->defineServices();
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function get(string $id): mixed
    {
        // Return an already resolved singleton
        if (isset($this->resolved[$id]) && ($this->singletons[$id] ?? true)) {
            return $this->resolved[$id];
        }

        if (!$this->has($id)) {
            throw new ServiceNotFoundException($id);
        }

        // Detect circular dependencies
        if (isset($this->resolving[$id])) {
            throw ContainerException::circularDependency($id);
        }

        try {
            $this->resolving[$id] = true;

            $factory = $this->factories[$id];
            $service = $factory($this);

            // Store singleton instances
            if ($this->singletons[$id] ?? true) {
                $this->resolved[$id] = $service;
            }

            return $service;
        } catch (Throwable $e) {
            if ($e instanceof ContainerExceptionInterface) {
                throw $e;
            }
            throw ContainerException::resolutionFailed($id, $e);
        } finally {
            unset($this->resolving[$id]);
        }
    }

    public function has(string $id): bool
    {
        return isset($this->factories[$id]);
    }

    /**
     * Register a service factory
     */
    public function bind(string $id, callable $factory, bool $singleton = true): self
    {
        $this->factories[$id] = $factory;
        $this->singletons[$id] = $singleton;

        // Clear a resolved instance if re-binding
        unset($this->resolved[$id]);

        return $this;
    }

    /**
     * Register a singleton service
     */
    public function singleton(string $id, callable $factory): self
    {
        return $this->bind($id, $factory);
    }

    /**
     * Register a transient service (new instance each time)
     */
    public function transient(string $id, callable $factory): self
    {
        return $this->bind($id, $factory, false);
    }

    /**
     * Bind a concrete instance
     */
    public function instance(string $id, mixed $instance): self
    {
        $this->resolved[$id] = $instance;
        $this->factories[$id] = fn() => $instance;
        $this->singletons[$id] = true;

        return $this;
    }

    /**
     * Get configuration value
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Set configuration value
     */
    public function setConfig(string $key, mixed $value): self
    {
        $this->config[$key] = $value;
        return $this;
    }

    /**
     * @deprecated Use bind() instead
     */
    public function set(string $id, callable $service): void
    {
        $this->bind($id, $service);
    }

    /**
     * @param array<string, mixed> $config
     * @throws ApiException
     */
    private function validateAndNormalizeConfig(array $config): array
    {
        if (empty($config['apiKey'])) {
            throw new ApiException('Missing required configuration: apiKey');
        }

        return array_merge([
            'useSandbox' => false,
            'timeout' => 30,
            'retryAttempts' => 1,
            'tokenCachePath' => sys_get_temp_dir() . '/novapost_sdk_jwt_token.json',
        ], $config);
    }

    private function add(string $id, callable $factory, bool $singleton = true): void
    {
        if ($this->has($id)) {
            return; // Don't override existing bindings
        }

        $this->bind($id, $factory, $singleton);
    }

    private function defineServices(): void
    {
        // Infrastructure services
        $this->add(
            LoggerInterface::class,
            fn() => new NullLogger()
        );

        $this->add(
            JwtTokenStorageInterface::class,
            fn() => new FileJwtTokenStorage($this->getConfig('tokenCachePath'))
        );

        // HTTP services
        $this->add(ClientInterface::class, function () {
            $baseUrl = $this->getConfig('useSandbox')
                ? NovaPostApi::SANDBOX_BASE_URL
                : NovaPostApi::PRODUCTION_BASE_URL;

            return new GuzzleClient([
                'base_uri' => $baseUrl,
                'timeout' => $this->getConfig('timeout'),
                'headers' => [
                    'User-Agent' => 'NovaPost-SDK/1.0',
                    'Accept' => 'application/json',
                ],
            ]);
        });

        $this->add(
            RequestFactoryInterface::class,
            fn() => new HttpFactory()
        );

        // Authentication services
        $this->add(
            AuthClient::class,
            fn(ContainerInterface $c) => new AuthClient(
                $c->get(ClientInterface::class),
                $c->get(RequestFactoryInterface::class),
                $c->get(LoggerInterface::class),
                $this->getConfig('apiKey')
            )
        );

        // Token management
        $this->add(
            TokenProviderInterface::class,
            fn(ContainerInterface $c) => new JwtTokenProvider(
                $c->get(JwtTokenStorageInterface::class),
                $c->get(AuthClient::class)
            )
        );

        // Alias for easier access
        $this->add(
            JwtTokenProvider::class,
            fn(ContainerInterface $c) => $c->get(TokenProviderInterface::class)
        );

        // HTTP handling services
        $this->add(
            ResponseValidatorInterface::class,
            fn(ContainerInterface $c) => new HttpResponseValidator()
        );

        $this->add(
            RetryHandlerInterface::class,
            fn(ContainerInterface $c) => new TokenRetryHandler(
                $c->get(ClientInterface::class),
                $c->get(TokenProviderInterface::class),
                $c->get(ResponseValidatorInterface::class),
                $c->get(LoggerInterface::class)
            )
        );

        // Main client
        $this->add(
            Client::class,
            fn(ContainerInterface $c) => new Client(
                $c->get(ClientInterface::class),
                $c->get(TokenProviderInterface::class),
                $c->get(ResponseValidatorInterface::class),
                $c->get(RetryHandlerInterface::class),
                $c->get(LoggerInterface::class)
            )
        );

        // Main API facade
        $this->add(
            NovaPostApi::class,
            fn(ContainerInterface $c) => new NovaPostApi(
                $c->get(Client::class)
            )
        );
    }
}
