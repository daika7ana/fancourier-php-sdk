<div align="center">

<h1>FAN Courier PHP SDK</h1>

[![CI](https://github.com/daika7ana/fancourier-php-sdk/actions/workflows/ci.yml/badge.svg)](https://github.com/daika7ana/fancourier-php-sdk/actions/workflows/ci.yml)
[![PHP 8.3+](https://img.shields.io/badge/PHP-8.3%2B-777BB4?style=flat-square&logo=php)](https://www.php.net/)
[![PHPUnit](https://img.shields.io/badge/PHPUnit-12%E2%80%9313-0A7BBB?style=flat-square)](docs/TESTING.md)
[![PSR Standards](https://img.shields.io/badge/PSR-7%2F17%2F18-blue?style=flat-square)](https://www.php-fig.org/)
[![License](https://img.shields.io/badge/license-GPL--3.0--or--later-green?style=flat-square)](LICENSE)

A modern, type-safe PHP SDK for the **FAN Courier API**

</div>

> 🚀 Framework-agnostic, PSR-18 native, fully typed with DTOs, and ready for domestic and export shipments.

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Configuration](#configuration)
- [Authentication](#authentication)
- [Supported Resources](#supported-resources)
- [Framework Support](#framework-support)
- [Testing](#testing)
- [Documentation](#documentation)

## Features

### Core Strengths

- ✅ **Bearer Authentication** — `POST /login`, with automatic re-authentication of expired tokens
- ✅ **PSR-18 HTTP Client** — Guzzle adapter by default, bring your own client if you prefer
- ✅ **Fully Typed DTOs** — AWB creation, tariffs, locations, PUDO points, orders and reports
- ✅ **Enums for API Values** — Services, AWB options, payment types, delivery modes and label formats
- ✅ **Binary Response Helpers** — AWB labels, ZIP confirmations and other non-JSON responses
- ✅ **Paginated Results** — `PaginatedResult` with a `Collection` of DTOs
- ✅ **Static Analysis with PHPStan** — Level 8 across `src/`
- ✅ **No Framework Coupling** — No framework in `require` and no framework imports in `src/`

### Perfect For

- 🎯 Domestic and export shipments
- 🎯 Type-safe AWB, tariff, tracking and label workflows
- 🎯 PHP applications of any framework, microservices and standalone projects

## Requirements

- **PHP 8.3+** with `ext-json`
- PSR-7 / PSR-17 / PSR-18 compatible environment
- A FAN Courier client id and account credentials

## Installation

### Via Composer

```bash
composer require daika7ana/fancourier-php-sdk
```

## Quick Start

Working with the FAN Courier API is straightforward:

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

That's it! You have an authenticated client, typed services, and an AWB number.

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

### Base URL

The client targets production at `https://api.fancourier.ro` by default. Only production is documented by FAN Courier; for a proxy or a local stub, pass a different `baseUrl`:

```php
$config = new ClientConfig(baseUrl: 'http://localhost:8080');
```

### Environment Variables

`ClientConfig::fromEnvironment()` is used by `Client::create()` and reads:

| Variable | Maps to |
|----------|---------|
| `FANCOURIER_CLIENT_ID` | `clientId` |
| `FANCOURIER_BASE_URL` | `baseUrl` |
| `FANCOURIER_USERNAME` | `username` |
| `FANCOURIER_PASSWORD` | `password` |
| `FANCOURIER_LANGUAGE` | `language` |
| `FANCOURIER_TIMEOUT` | `timeout` |

### Client Id

Every account-scoped endpoint — AWB, tariffs, orders, service options and most reports — needs the client id. The SDK appends it automatically; calling one of those methods without a configured id raises `ValidationException`. Reference-data calls (`services()->all()`, `locations()`, `pickupPoints()`, `branches()` and the event catalogues) do not send it.

### Timeouts

`timeout` sets the request timeout in seconds, including the login call. It defaults to `null`: the bundled `GuzzleHttpClient` then applies its own 30 s request / 10 s connect defaults, while a bare PSR-18 client keeps its own. `0` disables the cURL timeout.

`ClientConfig::timeout` is `null` by default. Precedence: the per-call argument → `ClientConfig::timeout` → the transport's own default (`GuzzleHttpClient` uses 30 s to respond and 10 s to connect unless you pass `timeout:`/`connectTimeout:`; an injected `GuzzleClient` keeps its own configuration). A bare PSR-18 client keeps its own timeout and cannot receive the per-call argument — passing one throws `InvalidArgumentException`; combining a bare client with a non-null `ClientConfig::timeout` also throws. `Client::send()` is `@internal`, so resource methods expose no timeout parameter.

## Authentication

### Bearer Tokens

```php
$client->authenticate(username: 'your-username', password: 'your-password');
```

The API returns a Bearer token valid for 24 hours. The SDK stores it in memory and sends it on every request. When `username` and `password` are configured it also re-authenticates automatically when the token expires (60 second safety margin) or when the API answers 401, and retries a 403 only when it is not a business failure; without credentials a rejected token surfaces as `UnexpectedStatusException`.

### Token Persistence

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

### Error Handling

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

## Framework Support

### Zero Framework Dependencies

The package keeps no framework in `require` and no framework imports in `src/`, so it works equally well in:

- 🌐 **Laravel** applications
- 🌐 **Symfony** projects
- 🌐 **Standalone PHP** applications
- 🌐 **Microservices**
- 🌐 Any **PHP 8.3+** environment

### Custom HTTP Client

Pass any PSR-18 client (auto-wrapped), or the bundled `GuzzleHttpClient` with your own Guzzle instance:

```php
use Daika7ana\FanCourier\Client;
use GuzzleHttp\Psr7\HttpFactory;

$factory = new HttpFactory();

/** @var \Psr\Http\Client\ClientInterface $psr18 */
$client = Client::create(
    httpClient: $psr18,
    requestFactory: $factory,
    streamFactory: $factory,
);
```

`GuzzleHttpClient` is the default when no client is supplied. A bare PSR-18 client keeps its own timeout, so a non-null `ClientConfig::timeout` alongside one throws `InvalidArgumentException`.

## Testing

### Run Static Analysis

```bash
composer stan
# or
vendor/bin/phpstan --no-progress
```

PHPStan is configured via `phpstan.neon` and analyzes the `src/` directory at level 8.

### Run Full Test Suite

```bash
composer test
# or
php vendor/bin/phpunit
```

Pass PHPUnit arguments through Composer with `--`:

```bash
composer test -- --filter=AwbResourceTest
composer test -- tests/Unit/Resources
```

### Run The Same Checks As CI

```bash
composer ci
```

Available Composer scripts:

- `composer pint` runs Pint in test mode
- `composer stan` runs PHPStan against `src/`
- `composer test:unit` runs the unit test suite
- `composer ci` runs the same local checks as CI

### Test Environment Variables

None. The suite is fully offline — no credentials, no network, no smoke suite, and no documented live sandbox. See [docs/TESTING.md](docs/TESTING.md) for the fake HTTP client and fixtures.

## Documentation

Complete guides and references:

| Guide | Purpose |
|-------|---------|
| 📖 [Quickstart](docs/QUICKSTART.md) | Jump right in with working examples |
| 🔧 [Installation](docs/INSTALLATION.md) | Detailed setup instructions |
| ⚙️ [Configuration](docs/CONFIGURATION.md) | Environment setup and options |
| 🔐 [Authentication](docs/AUTHENTICATION.md) | Bearer flow and token management |
| 🔗 [Resources](docs/RESOURCES.md) | Complete resource reference |
| 📦 [DTOs](docs/DTOS.md) | Data Transfer Objects and enums |
| ✅ [Testing](docs/TESTING.md) | Offline test suite and fixtures |
| ❌ [Errors](docs/ERRORS.md) | Exception handling guide |
| 📚 [All Docs](docs/) | Everything under `docs/` |

### Third-party material

`docs/RO_FANCourier_API_090126.pdf` is FAN Courier's own API documentation,
included unmodified as a reference. It is not covered by this project's
license: FAN Courier owns the document, its content, and the sample credentials
in its examples. Those credentials come from FAN Courier, not from this
project. They do not grant API access, and nothing here authorizes their use —
use them only if you are entitled to, and only against systems you are
authorized to test. This project is not affiliated with or endorsed by FAN
Courier.

---

<div align="center">

Made with ❤️

**Questions?** [Check the docs](docs/QUICKSTART.md) or [open an issue](../../issues)

</div>
