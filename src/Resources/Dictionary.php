<?php

declare(strict_types=1);

namespace NovaDigital\NovaPost\Resources;

use Psr\Http\Client\ClientExceptionInterface;

class Dictionary extends AbstractResource
{
    /**
     * Get a list of measurement units.
     *
     * @param array $params
     * @return array
     * @throws ClientExceptionInterface
     */
    public function measurements(array $params = []): array
    {
        return $this->sendRequest('GET', 'dictionary/measurements');
    }

    /**
     * Get a list of available currencies.
     *
     * @param array $params
     * @return array
     * @throws ClientExceptionInterface
     */
    public function currencies(array $params = []): array
    {
        return $this->sendRequest('GET', 'dictionary/currencies', $params);
    }

    /**
     * Search cargo classifiers (UKT ZED) for a keyword and country.
     *
     * @param array $params
     * @return array
     * @throws ClientExceptionInterface
     */
    public function cargoClassifiers(array $params): array
    {
        return $this->sendRequest('GET', 'dictionary/classifier', $params);
    }
}
