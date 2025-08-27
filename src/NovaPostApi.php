<?php

//todo copyrights

declare(strict_types=1);

namespace NovaDigital\NovaPost;

use NovaDigital\NovaPost\Resources\Division;
use NovaDigital\NovaPost\Resources\Shipment;
use NovaDigital\NovaPost\Resources\Service;
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
    public function services(): Service
    {
        return new Service($this->client);
    }
}
