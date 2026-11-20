# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.0.0/) and this project
adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-11-20

### Added

- `Client::create()` and `Client::__construct()` accept any PSR-18 `ClientInterface`; a bare client is wrapped in `Psr18HttpClientAdapter`.
- `Client::send()` accepts an optional per-call timeout, and `ClientConfig::withTimeout()` returns a config with that timeout.
- `GuzzleHttpClient::DEFAULT_TIMEOUT`/`DEFAULT_CONNECT_TIMEOUT` and `timeout:`/`connectTimeout:` constructor arguments.

### Changed

- **Breaking:** `ClientConfig::timeout` defaults to `null` instead of `30.0`. The bundled transport still applies 30 s request / 10 s connect, now including a 10 s connect cap that was previously absent. A custom `HttpClientInterface` receives `null` unless a timeout is configured.
- A non-null `ClientConfig::timeout` combined with a bare PSR-18 client throws `InvalidArgumentException`, and a per-call timeout passed to such a client throws too.
- A PSR-18 client that throws a `ClientExceptionInterface` is now wrapped in `TransportException`.

## [1.0.2] - 2026-11-19

### Added

- `InternalAwbRequest::$platformId` — the undocumented top-level `platformId`
  Fan Courier requires for some contracts; omitted from the payload when unset

## [1.0.1] - 2026-11-19

### Added

- `PickupPoint::$paymentMethods` — the payment instruments the point accepts
  (`card`, `cash`); the spec's sample response predates the field
- `PickupPoint::$schedule` now keeps the keys the API sends instead of
  re-indexing the list

## [1.0.0] - 2026-11-19

### Added

- `Client` with PSR-18 transport, lazy resources and a configurable `ClientConfig`
- Bearer authentication via `POST /login`, with automatic re-authentication of
  expired or rejected tokens when credentials are configured, and a pluggable
  `TokenStoreInterface`
- Domestic and export AWB creation, AWB deletion, label download (HTML/PDF/ZPL)
  and internal/external tariff calculation
- Service catalogue and service option lookup
- Counties, localities, streets, countries, external counties and external
  localities
- PUDO points (FANbox, PayPoint, FAN office) and branch lookup
- Courier order creation, cancellation, reporting, events and tracking
- AWB shipping slip, event catalogue, AWB tracking, bank transfers and ZIP
  confirmations
- Typed DTOs and enums, a `PaginatedResult` helper, and collections from
  `daika7ana/simple-collections`
- PHPUnit suite against a fake PSR-18 client, PHPStan level 8 and Pint
