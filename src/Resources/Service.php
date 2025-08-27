<?php

declare(strict_types=1);

namespace NovaDigital\NovaPost\Resources;

use Psr\Http\Client\ClientExceptionInterface;

class Service extends AbstractResource
{
    /**
     * Retrieve exchange rates for a specific amount and currency.
     *
     * @param array $params
     * @return array
     * @throws ClientExceptionInterface
     */
    public function exchangeRates(array $params): array
    {
        return $this->sendRequest('POST', 'exchange-rates/conversion', $params);
    }
}
