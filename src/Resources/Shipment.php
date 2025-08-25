<?php

declare(strict_types=1);

namespace NovaDigital\NovaPost\Resources;

use Psr\Http\Client\ClientExceptionInterface;

class Shipment extends AbstractResource
{
    /**
     * Create a new shipment.
     *
     * @param  array $params
     * @return array
     * @throws ClientExceptionInterface
     */
    public function create(array $params): array
    {
        return $this->sendRequest('POST', 'shipments', $params);
    }

    /**
     * Get a list of shipments.
     *
     * @param  array $params
     * @return array
     * @throws ClientExceptionInterface
     */
    public function get(array $params = []): array
    {
        return $this->sendRequest('GET', 'shipments', $params);
    }

    /**
     * Calculate the cost of a shipment.
     *
     * @param  array $params
     * @return array
     * @throws ClientExceptionInterface
     */
    public function calculate(array $params): array
    {
        return $this->sendRequest('POST', 'shipments/calculations', $params);
    }

    /**
     * Track a shipment.
     *
     * @param  array $params
     * @return array
     * @throws ClientExceptionInterface
     */
    public function track(array $params): array
    {
        return $this->sendRequest('GET', 'shipments/tracking', $params);
    }
}
