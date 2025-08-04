<?php

namespace NovaDigital\NovaPost\Storage;

interface JwtTokenStorageInterface
{
    public function save(string $token, int $ttlSeconds): void;

    public function get(): ?string;
}
