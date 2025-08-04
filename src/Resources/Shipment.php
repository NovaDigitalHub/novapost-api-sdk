<?php

declare(strict_types=1);

namespace NovaDigital\NovaPost\Resources;

class Shipment extends AbstractResource
{
    /**
     * Create a new shipment.
     *
     * @param array $params
     * @return array
     * @throws \NovaDigital\NovaPost\Exception\ApiException
     */
    public function create(array $params): array
    {
        return $this->sendRequest('POST', 'shipments', $params);
    }

    /**
     * Get a list of shipments.
     *
     * @param array $params
     * @return array
     * @throws \NovaDigital\NovaPost\Exception\ApiException
     */
    public function get(array $params = []): array
    {
        return $this->sendRequest('GET', 'shipments', $params);
    }

    /**
     * Calculate the cost of a shipment.
     *
     * @param array $params
     * @return array
     * @throws \NovaDigital\NovaPost\Exception\ApiException
     */
    public function calculate(array $params): array
    {
        return $this->sendRequest('POST', 'shipments/calculations', $params);
    }

    /**
     * Track a shipment.
     *
     * @param array $params
     * @return array
     * @throws \NovaDigital\NovaPost\Exception\ApiException
     */
    public function track(array $params): array
    {
        return $this->sendRequest('GET', 'shipments/tracking', $params);
    }
}
