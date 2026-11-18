# Authentication

FAN Courier issues a Bearer token from a username/password pair:

```http
POST /login?username=YOUR_USERNAME&password=YOUR_PASSWORD
```

```json
{"status": "success", "data": {"token": "YOUR_CLIENT_ID|abc...", "expiresAt": "2026-11-18 06:00:55"}}
```

The token is valid for 24 hours. It belongs to the *user*, while `clientId`
identifies the contract and is sent as a query/body parameter on the endpoints
that need it.

## Logging in

```php
$client->authenticate(username: 'your-username', password: 'your-password');
```

`authenticate()` stores the credentials in the client config, so the SDK can log
in again later without help.

## Automatic re-authentication

`Client::send()` handles expiry for you:

1. If no token is stored and credentials are configured, it logs in before the
   first request.
2. If the stored token is expired (with a 60 second safety margin), it logs in
   again before sending.
3. If the API still answers `401` or `403`, it logs in once and retries the
   request.

All three paths need `username` + `password` in the config. Without them the
request is sent with whatever token is stored — or with none — and a rejected
token surfaces as `UnexpectedStatusException`.

If the login itself fails, the SDK throws `AuthenticationException`.

## Accessing the token

```php
$token = $client->getToken();       // Auth\Token|null

if ($token !== null) {
    $token->accessToken;            // "7032158|abc..."
    $token->tokenType;              // "Bearer"
    $token->expiresAt;              // DateTimeImmutable|null
    $token->isExpired();            // bool, 60s margin applied
}
```

## Persisting a token

`Token` exposes `toArray()`/`fromArray()`, so a token store (cache, database,
session) can keep it between requests:

```php
use Daika7ana\FanCourier\Auth\Token;

// save
$cache->set('fancourier_token', $client->getToken()?->toArray());

// restore
$token = Token::fromArray($cache->get('fancourier_token'));
$client->setToken($token);
```

`Token::fromArray()` accepts `access_token`, `token_type` and `expires_at`
(ISO-8601 or any `DateTimeImmutable`-parseable string). `setToken()` also
accepts a raw string, in which case the token type defaults to `Bearer`.

To plug in a shared store, implement
`Daika7ana\FanCourier\Auth\TokenStoreInterface` and pass it to
`Client::create(tokenStore: $store)`. The default is `InMemoryTokenStore`.
