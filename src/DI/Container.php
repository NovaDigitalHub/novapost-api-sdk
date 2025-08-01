<?php

//todo copyrights

declare(strict_types=1);

namespace NovaDigital\NovaPost\DI;

use GuzzleHttp\Client as GuzzleClient;
use NovaDigital\NovaPost\Exception\ApiException;
use NovaDigital\NovaPost\Http\AuthenticationClient;
use NovaDigital\NovaPost\Http\Client;
use NovaDigital\NovaPost\JwtTokenProvider;
use NovaDigital\NovaPost\NovaPostApi;
use NovaDigital\NovaPost\Storage\FileJwtTokenStorage;
use NovaDigital\NovaPost\Storage\JwtTokenStorageInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Container implements ContainerInterface
{
    /**
     * @var array<string, int|string|bool>
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
     * @param array<string, int|string|bool> $config
     */
    public function __construct(array $config)
    {
        if (!isset($config['apiKey'])) {
            throw new ApiException('Missing apiKey');
        }

        $this->config = $config;
        $this->defineServices();
    }

    public function get(string $id)
    {
        if (isset($this->resolved[$id])) {
            return $this->resolved[$id];
        }

        if (!$this->has($id)) {
            throw new \Exception("Service not found: " . $id); // Replace with a custom exception
        }

        $factory = $this->factories[$id];
        $service = $factory($this);

        $this->resolved[$id] = $service;

        return $service;
    }

    public function has(string $id): bool
    {
        return isset($this->factories[$id]);
    }

    public function set(string $id, callable $service): void
    {
        $this->factories[$id] = $service;
        unset($this->resolved[$id]);
    }

    private function add(string $id, callable $factory): void
    {
        if ($this->has($id)) {
            return;
        }
        $this->factories[$id] = $factory;
    }

    private function defineServices(): void
    {
        $this->add(LoggerInterface::class, fn() => new NullLogger());
        $this->add(JwtTokenStorageInterface::class, fn() => new FileJwtTokenStorage());

        $this->add(ClientInterface::class, function () {
            $baseUrl = ($this->config['useSandbox'] ?? false)
                ? NovaPostApi::SANDBOX_BASE_URL
                : NovaPostApi::PRODUCTION_BASE_URL;
            return new GuzzleClient(['base_uri' => $baseUrl]);
        });

        $this->add(AuthenticationClient::class, fn(ContainerInterface $c) => new AuthenticationClient(
            $c->get(ClientInterface::class),
            $c->get(LoggerInterface::class),
            $this->config['apiKey']
        ));

        $this->add(JwtTokenProvider::class, fn(ContainerInterface $c) => new JwtTokenProvider(
            $c->get(JwtTokenStorageInterface::class),
            $c->get(AuthenticationClient::class)
        ));

        $this->add(Client::class, fn(ContainerInterface $c) => new Client(
            $c->get(ClientInterface::class),
            $c->get(JwtTokenProvider::class),
            $c->get(LoggerInterface::class)
        ));

        $this->add(NovaPostApi::class, fn(ContainerInterface $c) => new NovaPostApi(
            $c->get(Client::class)
        ));
    }
}
