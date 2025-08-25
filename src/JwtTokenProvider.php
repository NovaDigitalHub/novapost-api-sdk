<?php

declare(strict_types=1);

namespace NovaDigital\NovaPost;

use NovaDigital\NovaPost\Exception\ApiException;
use NovaDigital\NovaPost\Exception\AuthenticationException;
use NovaDigital\NovaPost\Exception\TokenExpiredException;
use NovaDigital\NovaPost\Exception\TokenRefreshException;
use NovaDigital\NovaPost\Http\AuthenticationClient;
use NovaDigital\NovaPost\Storage\JwtTokenStorageInterface;

class JwtTokenProvider
{
    public const JWT_TOKEN_LIFETIME_SECONDS = 60 * 60;
    private const JWT_TOKEN_EXPIRY_SKEW_SECONDS = 30;
    private JwtTokenStorageInterface $jwtTokenStorage;
    private AuthenticationClient $authenticator;
    private string $jwtToken;

    public function __construct(
        JwtTokenStorageInterface $jwtTokenStorage,
        AuthenticationClient $authenticator
    ) {
        $this->jwtTokenStorage = $jwtTokenStorage;
        $this->authenticator = $authenticator;
    }

    /**
     * @throws ApiException|AuthenticationException|TokenExpiredException
     */
    public function get(): string
    {
        if (isset($this->jwtToken)) {
            if ($this->isTokenExpired($this->jwtToken)) {
                unset($this->jwtToken);
                throw new TokenExpiredException('Cached token has expired');
            }

            return $this->jwtToken;
        }

        $jwtToken = $this->jwtTokenStorage->get();
        if ($jwtToken) {
            if ($this->isTokenExpired($jwtToken)) {
                $this->jwtTokenStorage->delete();
                throw new TokenExpiredException('Stored token has expired');
            }
            $this->jwtToken = $jwtToken;

            return $this->jwtToken;
        }

        $this->fetch();

        return $this->jwtToken;
    }

    /**
     * @throws TokenRefreshException
     */
    public function refresh(): string
    {
        try {
            unset($this->jwtToken);
            $this->jwtTokenStorage->delete();
            $this->fetch();

            return $this->jwtToken;
        } catch (AuthenticationException | ApiException $e) {
            throw new TokenRefreshException('Failed to refresh token: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws AuthenticationException|ApiException
     */
    private function fetch(): void
    {
        try {
            $this->jwtToken = $this->authenticator->getToken();
            $this->jwtTokenStorage->save($this->jwtToken, self::JWT_TOKEN_LIFETIME_SECONDS);
        } catch (AuthenticationException | ApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new AuthenticationException('Unexpected error: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Check if a JWT token is expired by decoding its payload
     */
    private function isTokenExpired(string $token): bool
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return true; // Invalid JWT format
            }

            $payload = json_decode(base64_decode($parts[1]), true);
            if (!$payload || !isset($payload['exp'])) {
                return true; // No expiration claim
            }

            return time() >= ($payload['exp'] - self::JWT_TOKEN_EXPIRY_SKEW_SECONDS);
        } catch (\Exception $e) {
            return true; // Can't decode the token
        }
    }
}
