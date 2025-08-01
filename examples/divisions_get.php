<?php

require_once __DIR__ . '/../vendor/autoload.php';

use NovaDigital\NovaPost\DI\Container;
use NovaDigital\NovaPost\NovaPostApi;
use NovaDigital\NovaPost\Exception\ApiException;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$apiKey = $_ENV['API_KEY'];
$useSandbox = boolval($_ENV['USE_SANDBOX']);

$calculationContent = file_get_contents('request.json');

try {
    $container = new Container(['apiKey' => $apiKey, 'useSandbox' => $useSandbox]);
    /** @var NovaPostApi $novaPostClient */
    $novaPostClient = $container->get(NovaPostApi::class);

    $novaPostClient->divisions()->get(['textSearch' => 'berlin']);
} catch (ApiException $e) {
    echo "Error API: " . $e->getMessage();
}