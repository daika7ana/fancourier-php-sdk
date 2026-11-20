# Installation

## Requirements

- PHP 8.3 or newer with `ext-json`
- A PSR-18 HTTP client (Guzzle is bundled as the default)
- A FAN Courier client id and account credentials

## Composer

```bash
composer require daika7ana/fancourier-php-sdk
```

This installs the PSR interfaces, `daika7ana/simple-collections`, and
`guzzlehttp/guzzle` for the bundled transport and PSR-17 factories.

## Framework integration

`Client::create()` builds a Guzzle client and PSR-17 factories for you. In an
application that already has them
(Laravel, Symfony), inject your own:

```php
use Daika7ana\FanCourier\Client;
use Daika7ana\FanCourier\Config\ClientConfig;

$client = Client::create(
    httpClient: new Daika7ana\FanCourier\Http\GuzzleHttpClient(
        $container->get(\GuzzleHttp\Client::class),
    ),
    requestFactory: $container->get(\Psr\Http\Message\RequestFactoryInterface::class),
    streamFactory: $container->get(\Psr\Http\Message\StreamFactoryInterface::class),
    config: new ClientConfig(clientId: 7032158),
);
```

`Client::create()` accepts any PSR-18 client. The SDK's
`Daika7ana\FanCourier\Http\HttpClientInterface` extends PSR-18 `ClientInterface`
with an optional per-request timeout argument; a bare PSR-18 client is wrapped
automatically, and `GuzzleHttpClient` is the default when none is supplied. A
bare PSR-18 client keeps its own timeout, so a non-null
`ClientConfig::timeout` alongside one throws `InvalidArgumentException`.

## Verifying the install

```php
use Daika7ana\FanCourier\Client;
use Daika7ana\FanCourier\Config\ClientConfig;

$client = Client::create(config: new ClientConfig(
    clientId: 7032158,
    username: 'your-username',
    password: 'your-password',
));

echo $client->services()->all()->count(); // number of services enabled for the account
```
