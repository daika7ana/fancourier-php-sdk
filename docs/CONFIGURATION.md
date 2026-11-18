# Configuration

`Daika7ana\FanCourier\Config\ClientConfig` holds every client-wide default. It is
`final readonly`: build it with named arguments and share it across clients.

| Property | Default | Purpose |
| --- | --- | --- |
| `baseUrl` | `https://api.fancourier.ro` | API root, without a trailing slash |
| `clientId` | `null` | Contract/client id sent on every account-scoped endpoint |
| `username` | `''` | Login used for automatic re-authentication |
| `password` | `''` | Login used for automatic re-authentication |
| `language` | `'ro'` | `language` query parameter for labels, events and tracking |
| `timeout` | `null` | Default HTTP timeout in seconds |

```php
use Daika7ana\FanCourier\Config\ClientConfig;

$config = new ClientConfig(
    clientId: 7032158,
    username: 'your-username',
    password: 'your-password',
    language: 'en',
    timeout: 30.0,
);
```

## Environment variables

`ClientConfig::fromEnvironment()` is the default used by `Client::create()`:

| Variable | Maps to |
| --- | --- |
| `FANCOURIER_CLIENT_ID` | `clientId` |
| `FANCOURIER_BASE_URL` | `baseUrl` |
| `FANCOURIER_USERNAME` | `username` |
| `FANCOURIER_PASSWORD` | `password` |
| `FANCOURIER_LANGUAGE` | `language` |
| `FANCOURIER_TIMEOUT` | `timeout` |

## Client id

Every endpoint that needs the contract id gets it from the client, not from the
call site. If the id is missing and no request DTO supplies one, the resource
throws `Daika7ana\FanCourier\Exceptions\ValidationException` before the request
is sent.

Request DTOs do not carry a `clientId`: the client injects its own id, so one
`Client` never sends for two contracts. For multiple contracts, build one
`Client` per id and share a `TokenStoreInterface` between them.

## Timeouts

`timeout` sets the timeout in seconds, applied to every request
the client makes, including the login call. Leave it `null` to use the HTTP
client's own default.

```php
$client = Client::create(config: new ClientConfig(clientId: 7032158, timeout: 5.0));
```

There is no per-call timeout: `Client::send()` takes only the request. To vary
timeouts, inject an `HttpClientInterface` implementation that handles them
itself. Keep the value positive — `0` disables the timeout in cURL.

## Base URL

Only production is documented by FAN Courier. For a proxy or a local stub:

```php
$config = new ClientConfig(baseUrl: 'http://localhost:8080');
```

## Client accessors

`Client::clientId()` returns the configured id or throws `ValidationException`.
`Client::getConfig()` returns the config the client was built from. The active
token is not part of it: read `Client::getToken()`, or persist it through a
`TokenStoreInterface`. `Token::fromResponse()` builds a `Token` from the raw
`data` block of a `POST /login` response.
