<?php

declare(strict_types=1);

namespace NovaDigital\NovaPost;

interface TokenProviderInterface
{
    public function get(): string;

    public function refresh(): string;
}
