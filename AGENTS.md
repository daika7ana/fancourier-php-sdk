# AGENTS.md

## Project

Typed PHP 8.3+ SDK for the FAN Courier API (`daika7ana/fancourier-php-sdk`,
namespace `Daika7ana\FanCourier`). Library only: no server, no web entrypoint, no
framework in `require`. PSR-18 core, one resource class per API area, readonly
DTOs, enums. Base URL `https://api.fancourier.ro`
(`ClientConfig::BASE_URL_PRODUCTION`), with no trailing `/api`, so paths start at
`/reports/...`, `/awb` or `/order`.

## Commands

```bash
composer install
composer ci         # validate --strict, pint --test, phpstan, phpunit tests/Unit
composer pint       # style check only, never fixes
composer stan       # PHPStan level 8 over src/
composer test       # full PHPUnit suite (tests/Unit + tests/Smoke)
composer test:unit  # tests/Unit only
composer test:smoke # tests/Smoke, self-skips without credentials
vendor/bin/pint     # the only way to auto-fix style
```

- `composer.lock` is gitignored, so CI resolves fresh; keep `guzzlehttp/psr7` at
  `>=2` because `Client::create()` instantiates `GuzzleHttp\Psr7\HttpFactory`.
- Quality gates: PHPStan level 8 over `src/` (vendor/tests excluded), Laravel
  Pint (`per` preset), PHPUnit strict (`.dist`), `composer validate --strict`.
  CI runs validate, Pint and PHPStan on PHP 8.3 and unit tests on 8.3/8.4/8.5,
  branch `master`.

## Layout

`src/` holds `Client.php`, `Config/`, `Auth/`, `Http/`, `Support/`, `Exceptions/`,
`Enums/`, `DTOs/<Area>/` and `Resources/`. `tests/Support/` holds the fakes,
`tests/Unit/` mirrors `src/`, `tests/Smoke/` is the live suite; `docs/` holds the
user guides and the API PDF.

## Conventions

- Never import Symfony, Laravel, or another framework into `src/`, `tests/`, or
  docs, and never make one a runtime dependency; framework integration is out of
  scope. No new runtime dependencies without an explicit decision. Requests are
  built through the injected PSR-17 factories and absolute URIs assembled
  internally; callers never pass a URL.
- DTOs are `final readonly` with `fromArray()`; request DTOs add `toArray()` that
  drops null optionals. Enum parameters are typed `Enum|string` and normalized in
  `toArray()`.
- `docs/` is user-facing documentation: behavior changes go in the matching file,
  not just in code. `CHANGELOG.md` is Keep a Changelog, newest first, tags `vX.Y.Z`.
- Commits and PR titles use Conventional Commits (`<type>(<scope>): <subject>`,
  lowercase imperative, no trailing period).

## HTTP Transport And Timeouts

- `Client` builds requests with the injected PSR-17 `RequestFactoryInterface` /
  `StreamFactoryInterface`, consumes PSR-7 `ResponseInterface`, and depends only
  on PSR-18 `Psr\Http\Client\ClientInterface` — never a concrete client.
- `src/Http/HttpClientInterface.php` widens PSR-18 with
  `sendRequest(RequestInterface $request, ?float $timeout = null)`.
  `src/Http/GuzzleHttpClient.php` is the bundled transport (Guzzle `http_errors`
  and redirects disabled, so 4xx/5xx reach the mappers);
  `src/Http/Psr18HttpClientAdapter.php` wraps any other PSR-18 client, rejects a
  non-null per-call timeout with `InvalidArgumentException`, and rethrows a
  foreign `ClientExceptionInterface` as `TransportException` (original as
  `previous`). `TransportException` implements `ClientExceptionInterface`.
- `Client::__construct()` throws `InvalidArgumentException` when
  `ClientConfig::timeout` is non-null and the client is not this package's
  `HttpClientInterface`. `ClientConfig::timeout` is `?float`, default `null`;
  `connectTimeout` exists only on
  `GuzzleHttpClient::__construct(?GuzzleClient $guzzleClient = null, ?float $timeout = null, ?float $connectTimeout = null)`,
  which applies `DEFAULT_TIMEOUT = 30.0` / `DEFAULT_CONNECT_TIMEOUT = 10.0` only
  when it creates its own Guzzle client. Precedence: per-call
  `Client::send($request, $t)` → `ClientConfig::timeout` → transport constructor
  arguments → 30.0/10.0.
- `guzzlehttp/guzzle` and `guzzlehttp/psr7` are runtime dependencies (default
  transport and `GuzzleHttp\Psr7\HttpFactory`), so `Client::create()` needs no
  arguments; no `class_exists` guard, no `suggest` entry.

## API Quirks

- Bearer auth: `POST /login` with the selfAWB credentials as query parameters.
  `Client::send()` re-authenticates an expired token and retries a rejected
  request once (401 always; 403 only when the body is a `status:success`
  envelope or not JSON), only when username and password are configured; there
  is no eager login.
  `TokenStoreInterface` (default `InMemoryTokenStore`) is the only token source —
  two contracts means two `Client` instances sharing one store.
- `ClientConfig` env: `FANCOURIER_BASE_URL`, `FANCOURIER_CLIENT_ID`,
  `FANCOURIER_USERNAME`, `FANCOURIER_PASSWORD`, `FANCOURIER_LANGUAGE`,
  `FANCOURIER_TIMEOUT`. `clientId` comes only from config — resources overwrite
  it even in raw-array requests.
- Business failures arrive as `{"status":"fail","message":…}` (often HTTP 200)
  and become `UnexpectedStatusException`. The HTTP status decides the rest:
  401 is `AuthenticationException`; 400 and 422 are `ValidationException`;
  403 and any other non-2xx are `UnexpectedStatusException`. Binary endpoints
  return `DTOs\Common\Document`.
- `createRequest()`, `withJsonBody()` and `send()` are `@internal`. Query arrays
  serialize as `key[]=` for lists, `1`/`0` for bools, nulls dropped.
- `PaymentType` sends `expeditor`/`destinatar`; `Altul` is rejected with 422. The
  PDF `docs/RO_FANCourier_API_090126.pdf` is the authoritative spec — there is no
  sandbox, so never present fixture-checked behavior as live-verified.
- The API returns some ids as `int` and others as `string`; map them through
  `JsonHelper::id()`, never cast to `int`.

## Tests

- Unit tests never touch the network. `TestClientFactory::create($handler)`
  returns `[Client, FakeHttpClient]` with `clientId` `7032158`;
  `Fixture::ok()/fail()/json()/raw()` cover the response envelopes.
- Assert method, path, query, body and headers on `$httpClient->lastRequest`;
  every new endpoint needs such a test covering both request and response
  mapping. `tests/Smoke/` logs in against the live API and self-skips unless
  `FANCOURIER_USERNAME`/`FANCOURIER_PASSWORD` are set; there is still no
  documented sandbox, so a green unit test proves the code matches the fixture,
  not the API. `composer ci` must pass before done.
- Contract-scoped smoke tests also skip unless `FANCOURIER_CLIENT_ID` is set;
  `FANCOURIER_SMOKE_AWB` points AWB tracking at a known AWB, otherwise today's
  shipping slip supplies one or the test skips.
- Write smoke tests run only with `FANCOURIER_SMOKE_ENABLE_WRITES=true`. Each
  one creates a record and deletes it in a `try/finally` in the same test; a
  failed delete fails the test, so the suite leaves no residue.
