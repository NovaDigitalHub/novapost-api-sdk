<?php

declare(strict_types=1);

namespace NovaDigital\NovaPost\Resources;

use Psr\Http\Client\ClientExceptionInterface;

class ExchangeRate extends AbstractResource
{
    /**
     * Convert an amount from one currency to another.
     *
     * @param array $params
     * @return array
     * @throws ClientExceptionInterface
     */
    public function convert(array $params): array
    {
        return $this->sendRequest('POST', 'exchange-rates/conversion', $params);
    }
}
