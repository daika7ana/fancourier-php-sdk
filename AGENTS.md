# OpenCode Repository Guide

PHP 8.3+ library — a typed SDK for the FAN Courier API. No server, no web
entrypoint, no framework dependency in `require`.

## Commands

```bash
composer ci        # pint -> stan -> test:unit (exactly what CI runs)
composer pint      # style check only — never fixes
composer stan      # PHPStan level 8 over src/
composer test      # every test (currently the same set as test:unit)
vendor/bin/pint    # the only way to actually fix style

composer test -- tests/Unit/Resources/AwbResourceTest.php
composer test -- --filter=AwbResourceTest
```

- `composer.lock` is gitignored: nothing is pinned, CI resolves fresh on every
  run. `guzzlehttp/psr7` must stay `>=2` because `Client::create()`
  instantiates `GuzzleHttp\Psr7\HttpFactory`.
- PHPStan inspects `src/` only, so a green `composer stan` says nothing about
  `tests/`.
- CI jobs: `pint` and `stan` on PHP 8.3 only; `test:unit` on 8.3/8.4/8.5,
  branch `master`.

## Testing

- `tests/Unit/` follows the `src/` layout. Build tests with
  `Daika7ana\FanCourier\Tests\Support\FakeHttpClient` (handler returns a real
  `GuzzleHttp\Psr7\Response`), `TestClientFactory::create($handler)` (returns
  `[Client, FakeHttpClient]`, clientId `7032158`) and `Fixture::ok()/fail()/raw()`.
  Use `FakeHttpClient` for resource tests; the Guzzle adapter is untested, so
  don't hand-roll PSR stubs to cover it.
- No live smoke tests: the API has no documented sandbox. Every wire detail in
  this SDK comes from the PDF in `docs/`, not from a real response — do not
  present fixture-checked behavior as verified against FAN Courier.

## Architecture

- `src/Client.php` is the only entrypoint; `Client::create()` wires Guzzle +
  `HttpFactory` unless factories are injected. Resources are lazy:
  `services()`, `awb()`, `locations()`, `pickupPoints()`, `orders()`,
  `reports()`, `branches()`.
- `ClientConfig::BASE_URL_PRODUCTION` is `https://api.fancourier.ro` with no
  trailing `/api`, so resource paths start at `/reports/...` or `/awb`.
- Auth lives in `src/Auth/`. The API issues a 24h Bearer token via
  `POST /login`. `Client::send()` logs in when no token is stored, refreshes an
  expired token, and retries a 401/403 once — all only when username/password
  are configured. The 60s expiry skew in `Token::isExpired()` is deliberate and
  covered in `TokenTest` / `ClientTest` — read those before touching `send()`.
- The client's configured id is the only `clientId` source: request DTOs do not
  accept one, and resources overwrite `clientId` even in raw-array requests.
  Two contracts means two `Client` instances sharing one `TokenStoreInterface`.
- `Client::createRequest()` serializes query arrays itself (`buildQuery`):
  lists become `key[]=`, bools `1`/`0`, nulls are dropped. Tariff resources
  bypass it with flat `info[service]` keys because the API documents a GET with
  a nested body.
- Business failures arrive as HTTP 200 `{"status":"fail","message":...}` and are
  converted to `ApiException` by `ApiResponseMapper::decodeJson()`; non-200 is
  `UnexpectedStatusException`; a Laravel 422 becomes `ValidationException`.
  Binary endpoints (labels, confirmations) return `DTOs/Common/Document` via
  `ApiResponseMapper::document()`, which still detects a JSON fail envelope.
  Do not send them through `decodeJson()`.
- `docs/RO_FANCourier_API_090126.pdf` is the authoritative endpoint spec. There
  is no text extraction in the repo; read the PDF when a path, parameter, or
  payload shape is in doubt.

## Conventions

- DTOs are `final readonly` with `fromArray()`; request DTOs add `toArray()`
  that drops null optionals. Enum parameters are typed `Enum|string` and
  normalized in `toArray()`.
- Five write methods accept `RequestDto|array`; the array branch is passed
  through untouched, so use enum backing values there (a backed enum object
  reaches `json_encode()` and throws a raw `JsonException`).
- Identifiers (`id`, `awbNumber`, order ids) are `int|string` and keep the wire
  type via `JsonHelper::id()` — `11` stays `int`, `"11"` stays a string. Do not
  add `(int)`/`(string)` casts for ids.
- `PaymentType` sends `expeditor`/`destinatar`/`Altul` (the PDF schema). Live
  check: tariffs accept `expeditor` and `destinatar` as well as `sender` and
  `recipient` (sender-paid returns 0 because the contract is billed), and
  report responses echo `sender`. `Altul` is rejected with 422 — do not use it
  until an AWB create proves otherwise.
- `docs/` is user-facing API documentation. Behavior changes go in the matching
  file (AUTHENTICATION, CONFIGURATION, DTOS, RESOURCES, …), not just in code.
- `CHANGELOG.md` is Keep a Changelog, newest version first, one
  `## [x.y.z] - YYYY-MM-DD` section per release; tags are `vX.Y.Z`. Do not add
  the link-reference block at the bottom.
