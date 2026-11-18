# FAN Courier PHP SDK

<div align="center">

[![CI](https://github.com/daika7ana/fancourier-php-sdk/actions/workflows/ci.yml/badge.svg)](https://github.com/daika7ana/fancourier-php-sdk/actions/workflows/ci.yml)
[![PHP 8.3+](https://img.shields.io/badge/PHP-8.3%2B-777BB4?style=flat-square&logo=php)](https://www.php.net/)
[![PHPUnit](https://img.shields.io/badge/PHPUnit-12%E2%80%9313-0A7BBB?style=flat-square)](docs/TESTING.md)
[![PSR Standards](https://img.shields.io/badge/PSR-7%2F17%2F18-blue?style=flat-square)](https://www.php-fig.org/)
[![License](https://img.shields.io/badge/license-GPL--3.0--or--later-green?style=flat-square)](LICENSE)

A modern, type-safe PHP SDK for the **FAN Courier API**

</div>

Framework-agnostic, PSR-18 native, fully typed request/response DTOs.

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Configuration](#configuration)
- [Authentication](#authentication)
- [Supported Resources](#supported-resources)
- [Error Handling](#error-handling)
- [Testing](#testing)
- [Documentation](#documentation)

## Features

- Bearer authentication against `POST /login`, with automatic re-authentication
  of expired tokens
- PSR-18 HTTP client, Guzzle adapter by default, or bring your own
- Fully typed DTOs for AWB creation, tariffs, locations, PUDO points, orders and reports
- Enums for services, AWB options, payment types, delivery modes and label formats
- Binary helpers for AWB labels, ZIP confirmations and other non-JSON responses
- Paginated results as `PaginatedResult` with a `Collection` of DTOs
- Static analysis with PHPStan level 8

## Requirements

- PHP 8.3+
- PSR-7 / PSR-17 / PSR-18 compatible environment

## Installation

```bash
composer require daika7ana/fancourier-php-sdk
```

## Quick Start

```php
use Daika7ana\FanCourier\Client;
use Daika7ana\FanCourier\Config\ClientConfig;

$client = Client::create(config: new ClientConfig(clientId: 7032158));

$client->authenticate(username: 'your-username', password: 'your-password');

$services = $client->services()->all();

foreach ($services as $service) {
    echo $service->id . ' ' . $service->name . PHP_EOL;
}
```

Create a domestic AWB:

```php
use Daika7ana\FanCourier\DTOs\Awb\InternalAwbRequest;
use Daika7ana\FanCourier\DTOs\Awb\InternalShipment;
use Daika7ana\FanCourier\DTOs\Awb\ShipmentInfo;
use Daika7ana\FanCourier\DTOs\Common\Address;
use Daika7ana\FanCourier\DTOs\Common\Contact;
use Daika7ana\FanCourier\DTOs\Common\Dimensions;
use Daika7ana\FanCourier\DTOs\Common\Packages;
use Daika7ana\FanCourier\Enums\AwbOption;
use Daika7ana\FanCourier\Enums\PaymentType;
use Daika7ana\FanCourier\Enums\ServiceType;

$result = $client->awb()->createInternal(new InternalAwbRequest(shipments: [
    new InternalShipment(
        info: new ShipmentInfo(
            service: ServiceType::Standard,
            packages: new Packages(parcel: 1),
            weight: 1,
            payment: PaymentType::Recipient,
            dimensions: new Dimensions(10, 10, 10),
            options: [AwbOption::EPod],
            content: 'Books',
        ),
        recipient: new Contact(
            name: 'Alex Popescu',
            phone: '0723456789',
            address: new Address(
                county: 'Bucuresti',
                locality: 'Bucuresti',
                street: 'Splaiul Independentei',
                streetNo: '1',
                zipCode: '050091',
            ),
        ),
    ),
]));

echo $result->first()?->awbNumber;
```

## Configuration

```php
use Daika7ana\FanCourier\Client;
use Daika7ana\FanCourier\Config\ClientConfig;

$config = new ClientConfig(
    clientId: 7032158,
    language: 'ro',
    timeout: 30.0,
);

$client = Client::create(config: $config);
```

`ClientConfig::fromEnvironment()` is used by `Client::create()` and reads
`FANCOURIER_CLIENT_ID`, `FANCOURIER_BASE_URL`, `FANCOURIER_USERNAME`,
`FANCOURIER_PASSWORD`, `FANCOURIER_LANGUAGE` and `FANCOURIER_TIMEOUT`.

Every account-scoped endpoint — AWB, tariffs, orders, service options and most
reports — needs the client id. The SDK appends it automatically; calling one of
those methods without a configured id raises `ValidationException`.
Reference-data calls (`services()->all()`, `locations()`, `pickupPoints()`,
`branches()` and the event catalogues) do not send it.

## Authentication

```php
$client->authenticate(username: 'your-username', password: 'your-password');
```

The API returns a Bearer token valid for 24 hours. The SDK stores it in memory
and sends it on every request. When `username` and `password` are configured it
also re-authenticates automatically when the token expires (60 second safety
margin) or when the API answers 401/403; without them a rejected token surfaces
as `UnexpectedStatusException`.

Persist and restore a token yourself:

```php
use Daika7ana\FanCourier\Auth\Token;

$token = $client->getToken();          // Token|null

if ($token !== null) {
    $client->setToken(Token::fromArray([   // restore
        'access_token' => $token->accessToken,
        'expires_at' => $token->expiresAt?->format(DATE_ATOM),
    ]));
}
```

## Supported Resources

| Accessor | Methods |
| --- | --- |
| `services()` | `all()`, `options(ServiceType\|string)` |
| `awb()` | `createInternal()`, `createExternal()`, `delete()`, `label()`, `internalTariff()`, `externalTariff()` |
| `locations()` | `counties()`, `localities()`, `streets()`, `countries()`, `externalCounties()`, `externalLocalities()` |
| `pickupPoints()` | `all(PickupPointType\|string)`, `get(string $id)` |
| `branches()` | `all(?county, ?locality)` |
| `orders()` | `create()`, `delete()`, `report()`, `events()`, `tracking()` |
| `reports()` | `shippingSlip()`, `awbEvents()`, `trackAwbs()`, `bankTransfers()`, `confirmations()` |

See [docs/RESOURCES.md](docs/RESOURCES.md) for endpoint-by-endpoint details.

## Error Handling

```php
use Daika7ana\FanCourier\Exceptions\ApiException;
use Daika7ana\FanCourier\Exceptions\ValidationException;

try {
    $client->awb()->createInternal($request);
} catch (ApiException $e) {
    // HTTP 200 {"status":"fail"} — business rejection from FAN Courier
    echo $e->getMessage();
} catch (ValidationException $e) {
    // HTTP 422 — Laravel validation errors
    print_r($e->errors);
}
```

All exceptions extend `Daika7ana\FanCourier\Exceptions\FanCourierException`.

## Testing

```bash
composer test
```

See [docs/TESTING.md](docs/TESTING.md) for the fake HTTP client and fixtures.

## Documentation

- [Installation](docs/INSTALLATION.md)
- [Quick Start](docs/QUICKSTART.md)
- [Configuration](docs/CONFIGURATION.md)
- [Authentication](docs/AUTHENTICATION.md)
- [Resources](docs/RESOURCES.md)
- [DTOs](docs/DTOS.md)
- [Errors](docs/ERRORS.md)
- [Testing](docs/TESTING.md)

## Third-party material

`docs/RO_FANCourier_API_090126.pdf` is FAN Courier's own API documentation,
included unmodified as a reference. It is not covered by this project's
license: FAN Courier owns the document, its content, and the sample credentials
in its examples. Those credentials come from FAN Courier, not from this
project. They do not grant API access, and nothing here authorizes their use —
use them only if you are entitled to, and only against systems you are
authorized to test. This project is not affiliated with or endorsed by FAN
Courier.

## License

GPL-3.0-or-later. See [LICENSE](LICENSE).
