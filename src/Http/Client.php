<?php

declare(strict_types=1);

namespace NovaDigital\NovaPost\Http;

use NovaDigital\NovaPost\Exception\ApiException;
use NovaDigital\NovaPost\Exception\AuthenticationException;
use NovaDigital\NovaPost\Exception\TokenExpiredException;
use NovaDigital\NovaPost\Exception\TokenRefreshException;
use NovaDigital\NovaPost\JwtTokenProvider;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class Client implements ClientInterface
{
    private ClientInterface $httpClient;
    private JwtTokenProvider $jwtTokenProvider;
    private LoggerInterface $logger;

    public function __construct(
        ClientInterface $httpClient,
        JwtTokenProvider $jwtTokenProvider,
        LoggerInterface $logger,
    ) {
        $this->httpClient = $httpClient;
        $this->jwtTokenProvider = $jwtTokenProvider;
        $this->logger = $logger;
    }

    /**
     * @throws ApiException|TokenExpiredException|TokenRefreshException|AuthenticationException
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $request = $request->withHeader('Authorization', $this->jwtTokenProvider->get());

        try {
            $response = $this->httpClient->sendRequest($request);
            $statusCode = $response->getStatusCode();

            if ($statusCode < 400) {
                return $response;
            }

            if ($statusCode === 401) {
                $this->logger->info('Received 401 Unauthorized', [
                    'status_code' => $statusCode,
                    'request_uri' => (string) $request->getUri()
                ]);

                throw new TokenExpiredException('Token expired', $statusCode);
            }

            if ($statusCode === 403) {
                throw new ApiException('Access forbidden', $statusCode);
            }

            if ($statusCode >= 500) {
                throw new ApiException('Server error', $statusCode);
            }

            throw new ApiException(sprintf('Request failed with status %d', $statusCode), $statusCode);
        } catch (TokenExpiredException $e) {
            try {
                $this->logger->info('Attempting to refresh expired token');
                $refreshedToken = $this->jwtTokenProvider->refresh();
                $request = $request->withHeader('Authorization', $refreshedToken);

                $response = $this->httpClient->sendRequest($request);
                $statusCode = $response->getStatusCode();

                if ($statusCode >= 400) {
                    throw new TokenRefreshException(
                        sprintf('Request failed after token refresh with status %d', $statusCode),
                        $statusCode
                    );
                }

                $this->logger->info('Token refresh successful, request completed');

                return $response;
            } catch (ClientExceptionInterface $refreshException) {
                $this->logger->error('Token refresh failed', [
                    'exception' => $refreshException->getMessage(),
                    'code' => $refreshException->getCode()
                ]);

                throw new TokenRefreshException(
                    'Failed to refresh token: ' . $refreshException->getMessage(),
                    $refreshException->getCode(),
                    $refreshException
                );
            } catch (\Exception $refreshException) {
                $this->logger->error('Token refresh failed with unexpected error', [
                    'exception' => $refreshException->getMessage(),
                    'code' => $refreshException->getCode()
                ]);

                throw new TokenRefreshException(
                    'Token refresh failed: ' . $refreshException->getMessage(),
                    $refreshException->getCode(),
                    $refreshException
                );
            }
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('HTTP request failed', [
                'exception' => $e->getMessage(),
                'code' => $e->getCode(),
                'request_uri' => (string) $request->getUri()
            ]);

            if ($e->getCode() === 401) { // other clients
                throw new TokenExpiredException('Token expired', 401, $e);
            }

            throw new ApiException('Request failed: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }
}
