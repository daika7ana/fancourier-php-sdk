# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.0.0/) and this project
adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
