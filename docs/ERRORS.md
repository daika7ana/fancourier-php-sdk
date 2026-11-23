# Errors

```
FanCourierException
├── AuthenticationException      login rejected or token unusable
├── ValidationException          HTTP 400 or 422, carries $errors
├── UnexpectedStatusException    any other non-2xx, or a {"status":"fail"} envelope
└── TransportException           network failure, implements PSR-18 ClientExceptionInterface
```

```php
use Daika7ana\FanCourier\Exceptions\AuthenticationException;
use Daika7ana\FanCourier\Exceptions\TransportException;
use Daika7ana\FanCourier\Exceptions\UnexpectedStatusException;
use Daika7ana\FanCourier\Exceptions\ValidationException;

try {
    $client->awb()->createInternal($request);
} catch (ValidationException $e) {
    // 400/422 with a Laravel-style error map
    print_r($e->errors);
} catch (AuthenticationException $e) {
    // bad credentials
} catch (UnexpectedStatusException $e) {
    // 404/500/..., a non-JSON body, or FAN Courier rejecting the shipment:
    // weight missing, invalid locality, ...
    echo $e->getMessage();
    $status = $e->response?->getStatusCode();
    $error = $e->error;              // ?FanCourierError
    $payload = $e->error?->raw;      // decoded failure envelope
} catch (TransportException $e) {
    // DNS, timeout, TLS: retry according to your own policy
}
```

## What maps where

The HTTP status alone decides the exception class:

| HTTP status | Exception |
| --- | --- |
| 401 (after the automatic retry) | `AuthenticationException` |
| 400 | `ValidationException` |
| 422 | `ValidationException` |
| 403 | `UnexpectedStatusException`; `$error->isAuthenticationRelated()` is still `true` |
| Any other non-2xx | `UnexpectedStatusException` |

Everything else that is not a mapped success:

| Situation | Exception |
| --- | --- |
| `{"status":"fail","message":"..."}` with HTTP 200 | `UnexpectedStatusException` |
| 4xx with `{"status":"fail","message":"..."}` (e.g. deleting an archived order → 403) | `UnexpectedStatusException` |
| Invalid JSON body | `UnexpectedStatusException` |
| Login returns `status: fail` or non-2xx | `AuthenticationException` |
| Missing `clientId` that the call needs | `ValidationException` |
| PSR-18 throws | `TransportException` |

## Notes

- Binary endpoints (labels, confirmations) go through
  `ApiResponseMapper::document()`: a PDF/ZIP body becomes a `Document`, a JSON
  fail envelope becomes `UnexpectedStatusException`, and a JSON *success* body —
  the API saying there is no file, e.g. no scans for those AWBs — also becomes
  `UnexpectedStatusException`, carrying the provider's message.
- `UnexpectedStatusException::$response` is nullable, so an unexpected status can
  be raised without a response in scope. `ValidationException::$response` and
  `AuthenticationException::$response` hold the raw PSR-7 response when the
  exception came from one.
- `AuthenticationException::$error`, `ValidationException::$error` and
  `UnexpectedStatusException::$error` carry a `FanCourierError` parsed from the
  response payload when one was available.
- `$exception->getCode()` is the HTTP status when the exception carries a
  response, and `0` otherwise. Carrier-specific codes are only on
  `$error->code`.
- The numeric `Retry-After` value in seconds is exposed as `$exception->retryAfter`
  on every exception that carries a response (`null` when absent, non-numeric, or
  an HTTP date).
- The client never retries business failures. Only authentication is retried,
  once, and only when credentials are configured.
- `401`, `400` and `422` are mapped before `$expectedStatus` is consulted, so a
  method that expects a different status still raises those classes.
- With credentials configured, a `403` whose body is a `status:success` envelope
  or is not JSON is retried once after a silent re-login before the caller sees
  anything. See [AUTHENTICATION.md](AUTHENTICATION.md).
- Wrong PHP argument types raise a `TypeError`, not a `ValidationException`.

## FanCourierError

`Daika7ana\FanCourier\DTOs\Common\Errors\FanCourierError` is the shared error
DTO carried by the exceptions above:

- `code` (HTTP status or the payload `code`), `message`, `id` (`id` or `error_id`),
  `raw` (decoded payload), `context`, `component`.
- `message` falls back through `message`, `error`, `general_error`, then
  `FanCourier API error.`.
- `context` and `component` are carrier-defined: FAN Courier may omit them, and
  their meaning is whatever that endpoint's payload says.
- `isValidationRelated()` is true for `400`/`422`, a non-empty `errors` array,
  or a message containing validation-style terms (`invalid`, `required`,
  `mandatory`, `missing`, `duplicate`, `already`, `not valid`, `validation`,
  `must be`).
- `isAuthenticationRelated()` is true for `401`/`403` or auth-related messages.

Both predicates are hints for your own handling; the exception class is chosen by
HTTP status alone.
