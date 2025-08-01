<?php

declare(strict_types=1);

namespace NovaDigital\NovaPost\Http;

use GuzzleHttp\Exception\GuzzleException;
use NovaDigital\NovaPost\Exception\ApiException;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;

class AuthenticationClient
{
    private const ENDPOINT_KEY = 'clients/authorization?apiKey=%s';
    private ClientInterface $httpClient;
    private LoggerInterface $logger;
    private string $apiKey;

    public function __construct(ClientInterface $httpClient, LoggerInterface $logger, string $apiKey)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->apiKey = $apiKey;
    }

    public function getToken(): string
    {
        try {
            $response = $this->httpClient->get(sprintf(self::ENDPOINT_KEY, $this->apiKey));
            $data = json_decode($response->getBody()->getContents(), true);

            if (empty($data['jwt'])) {
                throw new ApiException('Authentication failed: jwt-token not found in response.');
            }

            return $data['jwt'];
        } catch (GuzzleException $e) {
            $this->logger->error('Authentication failed', ['exception' => $e]);

            throw new ApiException('Authentication failed');
        }
    }
}
