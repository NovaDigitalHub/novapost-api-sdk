<?php

declare(strict_types=1);

namespace NovaDigital\NovaPost\Resources;

class Division extends AbstractResource
{
    public function get(array $params = []): array
    {
        return $this->sendRequest('GET', 'divisions', $params);
    }
}
