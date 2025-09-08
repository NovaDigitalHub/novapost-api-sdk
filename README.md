# NovaPost API SDK for PHP

This is the official PHP SDK for the [NovaPost API](https://api.novapost.com/developers/index.html). It provides a convenient and modern way to interact with the NovaPost API, allowing you to easily integrate shipping, tracking, and other services into your PHP application.

The SDK is built with modern best practices, including PSR compliance and a dependency injection container, making it flexible and easy to extend.

## Installation

You can install the SDK via [Composer](https://getcomposer.org/):

```bash
composer require novadigital/novapost-api-sdk
```

## Usage

### Initializing the Client

The easiest way to get started is by using the `NovaDigital\NovaPost\DI\Container`. It allows you to configure and create a client instance with just a few lines of code.

```php
require_once __DIR__ . '/vendor/autoload.php';

use NovaDigital\NovaPost\DI\Container;
use NovaDigital\NovaPost\Exception\ApiException;

// Your API key from your NovaPost account
$apiKey = 'YOUR_API_KEY'; 

try {
    $container = new Container(['apiKey' => $apiKey]);
    /** @var NovaPostApi $novaPostClient */
    $novaPostClient = $container->get(NovaPostApi::class);

} catch (ApiException $e) {
    // Handle exceptions during client creation or API calls
    echo "API Error: " . $e->getMessage();
}
```

### Using ContainerBuilder (Advanced Configuration)

For more advanced configuration, you can use the `ContainerBuilder` to customize various aspects of the client:

```php
use NovaDigital\NovaPost\DI\ContainerBuilder;
use Psr\Log\NullLogger;

// Create a configured container with a fluent interface
$container = (new ContainerBuilder())
    ->withApiKey('YOUR_API_KEY')
    ->withSandbox() // Enable sandbox mode for testing
    ->withTimeout(60) // Set a custom timeout in seconds
    ->build();

// Get the API client instance
$novaPostClient = $container->get(NovaPostApi::class);
```

### Making API Calls

Once you have the client, you can easily access the different API resources. For example, to get a list of divisions:

```php
try {
    // Get the first page of divisions
    $divisionsResponse = $novaPostClient->divisions()->get(['page' => 1, 'limit' => 10]);

    // The response is a typed data model
    echo "Total divisions: " . $divisionsResponse->getTotal() . "\n";

    // Loop through the items
    foreach ($divisionsResponse->getItems() as $division) {
        echo "Division Name: " . $division->getName() . "\n";
        echo "Address: " . $division->getAddress() . "\n\n";
    }

} catch (ApiException $e) {
    echo "API Error: " . $e->getMessage();
}
```

### Calculating Shipment Cost

Here's how you can calculate the cost of a shipment:

```php
try {
    $shipmentData = [
        // ... add your shipment calculation data here
    ];

    $calculationResult = $novaPostClient->shipments()->calculate($shipmentData);
    
    // Handle the result...

} catch (ApiException $e) {
    echo "API Error: " . $e->getMessage();
}
```

## Advanced Usage: Overriding Services

The SDK uses a PSR-11 dependency injection container, which gives you complete control over its internal components. You can replace any default service (like the logger or HTTP client) with your own implementation.

This is useful if you want to:
*   Integrate the SDK with your framework's logging system.
*   Customize the HTTP client with special middleware or configurations.
*   Use a different JWT token storage mechanism.

To override a service, you can pass your own instance to the factory:

```php
use My\Custom\HttpClient;
use My\Custom\JwtTokenStorage;
use My\Custom\Logger;
use NovaDigital\NovaPost\DI\Container;
use NovaDigital\NovaPost\Storage\JwtTokenStorageInterface
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;

// Example: Using a custom logger and HTTP client
$myLoggerFactory = fn => new Logger();
$myHttpClientFactory = fn => new HttpClient();
$jwtTokenStorageFactory = fn => new JwtTokenStorage();

$container = new Container(['apiKey' => 'YOUR_API_KEY']);

// Override the default services with your own
$container->set(LoggerInterface::class, $myLoggerFactory);
$container->set(ClientInterface::class, $myHttpClientFactory);
$container->set(JwtTokenStorageInterface::class, $jwtTokenStorageFactory);

$novaPostClient = $container->get(NovaPostApi::class);
```

Or with ContainerBuilder:

```php
// Build the container with custom services
$container = (new ContainerBuilder())
    ->withApiKey('YOUR_API_KEY')
    ->withSandbox()
    ->withLogger($logger)  // Override default logger
    ->withHttpClient($httpClient)  // Override default HTTP client
    ->withTokenStorage(new CustomTokenStorage())  // Override default token storage
    ->build();

// Get the API client with all custom services
$novaPostClient = $container->get(NovaPostApi::class);
```

This approach allows you to:
1. Replace any service in the dependency injection container
2. Maintain a clean, fluent interface for configuration
3. Keep your custom service implementations separate from the SDK
4. Test with mock services

### Available Service Overrides

You can override the following services using the `ContainerBuilder`:

- `LoggerInterface`: For custom logging
- `ClientInterface`: For custom HTTP client configuration
- `JwtTokenStorageInterface`: For custom JWT token storage
- `ResponseHandlerInterface`: For custom response handling
- `RetryHandlerInterface`: For custom retry logic

Each service can be overridden using the corresponding `with*` method on the `ContainerBuilder`.

## PSR Standards Compliance

This SDK adheres to the following PSR standards, ensuring interoperability and modern, high-quality code:

* **PSR-4: Autoloader**: For autoloading classes.
* **PSR-11: Container Interface**: For a flexible dependency injection container.
* **PSR-3: Logger Interface**: Allowing you to use any compatible logger.
* **PSR-7: HTTP Message Interface**: Used for all API requests and responses.
* **PSR-18: HTTP Client**: For sending HTTP requests.
* **PSR-17: HTTP Factories**: Used internally for creating PSR-7 messages.
* **PSR-12: Extended Coding Standard**: For coding styles.

This commitment to standards makes the SDK reliable, predictable, and easy to integrate into any modern PHP application.
