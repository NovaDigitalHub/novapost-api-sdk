<?php

declare(strict_types=1);

namespace NovaDigital\NovaPost\Http;

use GuzzleHttp\Exception\GuzzleException;
use NovaDigital\NovaPost\Exception\ApiException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;

class AuthenticationClient
{
    private const ENDPOINT_KEY = 'clients/authorization?apiKey=%s';
    private ClientInterface $httpClient;
    private RequestFactoryInterface $requestFactory;
    private LoggerInterface $logger;
    private string $apiKey;

    public function __construct(
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        LoggerInterface $logger,
        string $apiKey
    ) {
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->logger = $logger;
        $this->apiKey = $apiKey;
    }

    /**
     * @throws ApiException|ClientExceptionInterface
     */
    public function getToken(): string
    {
        try {
            $request = $this->requestFactory->createRequest(
                'GET',
                sprintf(self::ENDPOINT_KEY, $this->apiKey)
            );

            $response = $this->httpClient->sendRequest($request);
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
