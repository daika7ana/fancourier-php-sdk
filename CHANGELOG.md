# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.0.0/) and this project
adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.5.0] - 2026-11-26

### Changed

- **Breaking:** `internalTariff()` and `externalTariff()` throw `UnexpectedStatusException` when a success response carries no `data` object, instead of returning an empty `Tariff`.

## [1.4.0] - 2026-11-25

### Changed

- `ValidationException::$errors` defaults to `[]` and `$previous` accepts any `Throwable`, aligning the constructor with the sibling SDKs.

## [1.3.0] - 2026-11-23

### Added

- Expanded `tests/Smoke` to cover services, locations, branches, pickup points, order and AWB reporting, AWB tracking and an opt-in AWB create/delete cycle.

### Changed

- **Breaking:** `ApiException` is removed; business failures (`{"status":"fail"}`) now throw `UnexpectedStatusException`, which carries the same decoded payload on `$error` (`FanCourierError`) and `$error->raw`.
- **Breaking:** `UnexpectedStatusException::$response` is nullable and defaults to `null`.
- **Breaking:** HTTP 401 now throws `AuthenticationException` instead of `UnexpectedStatusException`.
- **Breaking:** HTTP 400 now throws `ValidationException` instead of `UnexpectedStatusException`.
- **Breaking:** `getCode()` returns the HTTP status when the exception carries a response, and the passed code (`0` by default) otherwise; the HTTP exception constructors now derive it from `$response`.
- `AuthenticationException` and `ValidationException` expose the parsed payload on `$error` (`FanCourierError`).
- `FanCourierError::isValidationRelated()` also recognises HTTP 400 and carrier validation-style messages.

### Added

- `DTOs\Common\Errors\FanCourierError` for the decoded error envelope, with `isValidationRelated()` and `isAuthenticationRelated()`.
- `tests/Smoke` live suite, self-skipping without `FANCOURIER_USERNAME`/`FANCOURIER_PASSWORD`, plus the `composer test:smoke` script.
- `.gitattributes` marking tests, docs and dev files as `export-ignore`.

## [1.2.0] - 2026-11-22

### Added

- `Client::withTimeout(?float $timeout)` derives a client sharing the transport, PSR-17 factories and token store, carrying the credentials configured when it is derived, with a different request timeout budget — no second login, no new transport is built.
- `ApiException`, `ValidationException` and `AuthenticationException` now expose
  the raw PSR-7 response on `$exception->response` (`?ResponseInterface`) when the
  failure came from an API response, matching `UnexpectedStatusException`. All
  four expose the numeric `Retry-After` value in seconds on
  `$exception->retryAfter` (`null` when absent).

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
