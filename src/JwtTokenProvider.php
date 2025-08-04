<?php

declare(strict_types=1);

namespace NovaDigital\NovaPost;

use NovaDigital\NovaPost\Http\AuthenticationClient;
use NovaDigital\NovaPost\Storage\JwtTokenStorageInterface;

class JwtTokenProvider
{
    public const JWT_TOKEN_LIFE_TIME = 60 * 60;
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

    public function get(): string
    {
        if (isset($this->jwtToken)) {
            return $this->jwtToken;
        }

        $jwtToken = $this->jwtTokenStorage->get();
        if ($jwtToken) {
            $this->jwtToken = $jwtToken;
            return $this->jwtToken;
        }

        $this->fetch();

        return $this->jwtToken;
    }

    public function refresh(): string
    {
        $this->fetch();

        return $this->jwtToken;
    }

    private function fetch(): void
    {
        $this->jwtToken = $this->authenticator->getToken();
        $this->jwtTokenStorage->save($this->jwtToken, self::JWT_TOKEN_LIFE_TIME);
    }
}
