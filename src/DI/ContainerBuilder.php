<?php

declare(strict_types=1);

namespace NovaDigital\NovaPost\DI;

use NovaDigital\NovaPost\Exception\ApiException;
use NovaDigital\NovaPost\Storage\JwtTokenStorageInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;

class ContainerBuilder
{
    private array $config = [];
    private array $bindings = [];

    public function withApiKey(string $apiKey): self
    {
        $this->config['apiKey'] = $apiKey;
        return $this;
    }

    public function withSandbox(bool $useSandbox = true): self
    {
        $this->config['useSandbox'] = $useSandbox;
        return $this;
    }

    public function withTimeout(int $timeout): self
    {
        $this->config['timeout'] = $timeout;
        return $this;
    }

    public function withLogger(LoggerInterface $logger): self
    {
        $this->bindings[LoggerInterface::class] = fn() => $logger;
        return $this;
    }

    public function withTokenStorage(JwtTokenStorageInterface $storage): self
    {
        $this->bindings[JwtTokenStorageInterface::class] = fn() => $storage;
        return $this;
    }

    public function withHttpClient(ClientInterface $httpClient): self
    {
        $this->bindings[ClientInterface::class] = fn() => $httpClient;
        return $this;
    }

    /**
     * @throws ApiException
     */
    public function build(): Container
    {
        $container = new Container($this->config);

        foreach ($this->bindings as $id => $factory) {
            $container->bind($id, $factory);
        }

        return $container;
    }
}
