<?php

require_once __DIR__ . '/../vendor/autoload.php';

use NovaDigital\NovaPost\DI\Container;
use NovaDigital\NovaPost\NovaPostApi;
use NovaDigital\NovaPost\Exception\ApiException;
use Psr\Http\Client\ClientExceptionInterface;
use NovaDigital\NovaPost\Resources\Division;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$apiKey = $_ENV['API_KEY'];
$useSandbox = boolval($_ENV['USE_SANDBOX']);

try {
    $container = new Container(['apiKey' => $apiKey, 'useSandbox' => $useSandbox]);
    /** @var NovaPostApi $novaPostClient */
    $novaPostClient = $container->get(NovaPostApi::class);

    $searchParams = [
        'textSearch' => 'berlin',
        'divisionCategories' => [Division::DIVISION_CATEGORY_POSTOMAT]
    ];

    $divisions = $novaPostClient->divisions()->get($searchParams);

    echo "Success: Retrieved " . count($divisions) . " divisions\n";
} catch (ApiException $e) {
    echo "API Error: " . $e->getMessage() . " (Code: " . $e->getCode() . ")\n";
} catch (ClientExceptionInterface $e) {
    echo "HTTP Client Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
}
