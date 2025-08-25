<?php

declare(strict_types=1);

namespace NovaDigital\NovaPost\Resources;

use Psr\Http\Client\ClientExceptionInterface;

class Division extends AbstractResource
{
    public const DIVISION_CATEGORY_POST_BRANCH = 'PostBranch';
    public const DIVISION_CATEGORY_CARGO_BRANCH = 'CargoBranch';
    public const DIVISION_CATEGORY_PUDO = 'PUDO';

    /**
     * @throws ClientExceptionInterface
     */
    public function get(array $params = []): array
    {
        return $this->sendRequest('GET', 'divisions', $params);
    }
}
