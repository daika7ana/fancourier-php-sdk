# Installation

## Requirements

- PHP 8.3 or newer with `ext-json`
- A PSR-18 HTTP client (Guzzle 7/8 is installed by default)
- A FAN Courier client id and account credentials

## Composer

```bash
composer require daika7ana/fancourier-php-sdk
```

This installs `guzzlehttp/guzzle` (with its own runtime dependencies),
`guzzlehttp/psr7` and the PSR interfaces. There is no framework dependency in
`require`.

## Framework integration

`Client::create()` builds a Guzzle client and PSR-17 factories for you. In an
application that already has them (Laravel, Symfony), inject your own:

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

`Client::create()` accepts anything implementing
`Daika7ana\FanCourier\Http\HttpClientInterface`, which extends PSR-18
`ClientInterface` with an optional per-request timeout argument. A bare PSR-18
client must be wrapped in an adapter; the shipped one is `GuzzleHttpClient`.

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
