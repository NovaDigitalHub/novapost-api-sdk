<?php

declare(strict_types=1);

namespace NovaDigital\NovaPost\Resources;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\ClientExceptionInterface;

abstract class AbstractResource
{
    protected ClientInterface $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @throws ClientExceptionInterface
     */
    protected function sendRequest(string $method, string $uri, array $data = []): array
    {
        $body = null;
        if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $body = json_encode($data);
        }

        if ($method === 'GET' && !empty($data)) {
            $uri .= '?' . http_build_query($data);
        }

        $request = new Request($method, $uri, [], $body);

        if ($body !== null) {
            $request = $request->withHeader('Content-Type', 'application/json');
        }

        $response = $this->client->sendRequest($request);

        return json_decode($response->getBody()->getContents(), true);
    }
}
