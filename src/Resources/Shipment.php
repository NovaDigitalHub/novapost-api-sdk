<?php

declare(strict_types=1);

namespace NovaDigital\NovaPost\Resources;

use Psr\Http\Client\ClientExceptionInterface;

class Shipment extends AbstractResource
{
    /**
     * Create a new shipment.
     *
     * @param array $params
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
     * @param array $params
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
     * @param array $params
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
     * @param array $params
     * @return array
     * @throws ClientExceptionInterface
     */
    public function track(array $params): array
    {
        return $this->sendRequest('GET', 'shipments/tracking', $params);
    }

    /**
     * Retrieve printable shipment documents (PDF).
     *
     * @param array $params
     * @return array|string Validation error or binary PDF content
     * @throws ClientExceptionInterface
     */
    public function print(array $params): array|string
    {
        return $this->sendRequest('GET', 'shipments/print', $params);
    }

    /**
     * Update a shipment by ID. Replace the old shipment with the new data.
     *
     * @param string $id The ID of the shipment to update
     * @param array $params The shipment data to update
     * @return array
     * @throws ClientExceptionInterface
     */
    public function update(string $id, array $params): array
    {
        return $this->sendRequest('PUT', "shipments/{$id}", $params);
    }

    /**
     * Delete a shipment by ID or tracking number.
     *
     * @param string $idOrTrackingNumber The ID or tracking number (e.g., SHPL0123456789) of the shipment to delete
     * @return array
     * @throws ClientExceptionInterface
     */
    public function delete(string $idOrTrackingNumber): array
    {
        return $this->sendRequest('DELETE', "shipments/{$idOrTrackingNumber}");
    }
}
