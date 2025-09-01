<?php

declare(strict_types=1);

namespace NovaDigital\NovaPost;

use NovaDigital\NovaPost\Resources\ExchangeRate;
use NovaDigital\NovaPost\Resources\Dictionary;
use NovaDigital\NovaPost\Resources\Division;
use NovaDigital\NovaPost\Resources\Shipment;
use Psr\Http\Client\ClientInterface;

/**
 * @api
 */
final class NovaPostApi
{
    public const PRODUCTION_BASE_URL = 'https://api.novapost.com/v.1.0/';
    public const SANDBOX_BASE_URL = 'https://api-stage.novapost.pl/v.1.0/';

    private ClientInterface $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @api
     */
    public function divisions(): Division
    {
        return new Division($this->client);
    }

    /**
     * @api
     */
    public function shipments(): Shipment
    {
        return new Shipment($this->client);
    }

    /**
     * @api
     */
    public function exchangeRates(): ExchangeRate
    {
        return new ExchangeRate($this->client);
    }

    /**
     * @api
     */
    public function dictionary(): Dictionary
    {
        return new Dictionary($this->client);
    }
}
