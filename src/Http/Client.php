<?php

declare(strict_types=1);

namespace NovaDigital\NovaPost\Http;

use GuzzleHttp\Exception\GuzzleException;
use NovaDigital\NovaPost\Exception\ApiException;
use NovaDigital\NovaPost\JwtTokenProvider;
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

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $request = $request->withHeader('Authorization', $this->jwtTokenProvider->get());

        try {
            return $this->httpClient->sendRequest($request);
        } catch (GuzzleException $e) {
            $this->logger->error($e->getMessage()); //todo provide format message

            if ($e->getCode() === 401) { //todo validate when expired and unauthorised
                $request = $request->withHeader('Authorization', $this->jwtTokenProvider->refresh());
                return $this->httpClient->sendRequest($request);
            }
            throw new ApiException('HTTP request failed: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }
}
