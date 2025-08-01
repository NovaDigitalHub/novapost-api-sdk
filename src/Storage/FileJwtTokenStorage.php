<?php

declare(strict_types=1);

namespace NovaDigital\NovaPost\Storage;

class FileJwtTokenStorage implements JwtTokenStorageInterface
{
    private string $filePath;

    public function __construct(?string $filePath = null)
    {
        $this->filePath = $filePath ?? sys_get_temp_dir() . '/novapost_sdk_jwt_token.json';
    }

    public function save(string $token, int $ttlSeconds): void
    {
        $data = [
            'token' => $token,
            'expires_at' => time() + $ttlSeconds,
        ];

        file_put_contents($this->filePath, json_encode($data), LOCK_EX);
    }

    public function get(): ?string
    {
        if (!file_exists($this->filePath)) {
            return null;
        }

        $content = file_get_contents($this->filePath);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || empty($data['token']) || time() > $data['expires_at']) {
            @unlink($this->filePath);
            return null;
        }

        return $data['token'];
    }
}
