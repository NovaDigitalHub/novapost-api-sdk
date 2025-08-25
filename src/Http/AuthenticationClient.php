<?php

declare(strict_types=1);

namespace NovaDigital\NovaPost\Http;

use NovaDigital\NovaPost\Exception\ApiException;
use NovaDigital\NovaPost\Exception\AuthenticationException;
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
     * @throws AuthenticationException|ApiException
     */
    public function getToken(): string
    {
        try {
            $request = $this->requestFactory->createRequest(
                'GET',
                sprintf(self::ENDPOINT_KEY, $this->apiKey)
            );

            $response = $this->httpClient->sendRequest($request);
            $statusCode = $response->getStatusCode();

            if ($statusCode >= 400) {
                $errorMessage = sprintf('Authentication failed with status %d', $statusCode);
                $this->logger->error($errorMessage, ['status_code' => $statusCode]);

                if ($statusCode === 401) {
                    throw new AuthenticationException('Invalid API key or unauthorized access', $statusCode);
                } elseif ($statusCode === 403) {
                    throw new AuthenticationException('Access forbidden - check API key permissions', $statusCode);
                } elseif ($statusCode >= 500) {
                    throw new ApiException('Authentication server error - please try again later', $statusCode);
                } else {
                    throw new AuthenticationException($errorMessage, $statusCode);
                }
            }

            $data = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $jsonError = json_last_error_msg();
                $this->logger->error('Authentication response JSON decode failed', ['error' => $jsonError]);
                throw new ApiException('Invalid JSON response from authentication server: ' . $jsonError);
            }

            if (empty($data['jwt'])) {
                $this->logger->error('JWT token not found in authentication response');
                throw new AuthenticationException('Authentication failed: JWT token not found in response');
            }

            return $data['jwt'];
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('Authentication request failed', [
                'exception' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            throw new AuthenticationException('Authentication request failed: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }
}
