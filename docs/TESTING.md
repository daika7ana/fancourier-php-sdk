# Testing

```bash
composer test                                  # whole suite (unit + smoke)
composer test -- tests/Unit/Resources          # one directory
composer test -- --filter=AwbResourceTest      # one class
composer test:smoke                            # live smoke suite only
composer ci                                    # validate + pint + phpstan + unit tests
```

`tests/Unit` is fully offline: no credentials, no network. `tests/Smoke` calls
the live API but self-skips unless `FANCOURIER_USERNAME` and
`FANCOURIER_PASSWORD` are set, so `composer test` and `composer ci` pass offline.
There is no documented sandbox, so a green unit test proves the code matches the
fixture, not the API.

## Smoke tests

With `FANCOURIER_USERNAME` and `FANCOURIER_PASSWORD` in the environment — and
`FANCOURIER_CLIENT_ID` when the account needs a specific contract:

```bash
composer test:smoke
```

`tests/Smoke` logs in and fetches the service catalogue. It is the only suite
that talks to FAN Courier, and it is skipped, not failed, when the credentials
are absent.

## Fake HTTP client

`Daika7ana\FanCourier\Tests\Support\FakeHttpClient` records the last request and
returns whatever your handler produces. `TestClientFactory::create($handler)`
builds a client wired to it with `clientId` `7032158`:

```php
use Daika7ana\FanCourier\Tests\Support\Fixture;
use Daika7ana\FanCourier\Tests\Support\TestClientFactory;

[$client, $http] = TestClientFactory::create(
    static fn() => Fixture::ok([['id' => 1, 'name' => 'Standard']]),
);

$services = $client->services()->all();

self::assertSame('/reports/services', $http->lastRequest?->getUri()->getPath());
```

## Fixtures

`Fixture` covers the three response shapes the API uses:

| Helper | Response |
| --- | --- |
| `Fixture::ok($data)` | `{"status":"success","data":...}` |
| `Fixture::fail($message)` | `{"status":"fail","message":...}` |
| `Fixture::json($payload, $status)` | arbitrary JSON, for 422/500 cases |
| `Fixture::raw($body, $status, $headers)` | arbitrary body, for binary endpoints |

Assert on `$http->lastRequest`: method, path, query string
(`$request->getUri()->getQuery()`), headers and `(string) $request->getBody()`.

## What the tests cover

- `ClientTest` — query serialization, authorization header, automatic login and
  the one-shot 401 retry
- `Auth/*` — token parsing, expiry margin, login failures
- `Resources/*` — path, query, body and response mapping for every resource
- `DTOs/*` — acceptance of raw API shapes, null handling, enum normalization
