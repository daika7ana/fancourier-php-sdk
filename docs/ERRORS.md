# Errors

```
FanCourierException
├── ApiException                 {"status":"fail","message":...}, any HTTP status
├── AuthenticationException      login rejected or token unusable
├── ValidationException          HTTP 422, carries $errors
├── UnexpectedStatusException    any other non-2xx, carries the response
└── TransportException           network failure, implements PSR-18 ClientExceptionInterface
```

```php
use Daika7ana\FanCourier\Exceptions\ApiException;
use Daika7ana\FanCourier\Exceptions\AuthenticationException;
use Daika7ana\FanCourier\Exceptions\TransportException;
use Daika7ana\FanCourier\Exceptions\UnexpectedStatusException;
use Daika7ana\FanCourier\Exceptions\ValidationException;

try {
    $client->awb()->createInternal($request);
} catch (ApiException $e) {
    // FAN Courier rejected the shipment: weight missing, invalid locality, ...
    echo $e->getMessage();
    $payload = $e->payload;          // decoded failure envelope
} catch (ValidationException $e) {
    // 422 with a Laravel-style error map
    print_r($e->errors);
} catch (AuthenticationException $e) {
    // bad credentials
} catch (UnexpectedStatusException $e) {
    // 404/500/... or a non-JSON body
    $response = $e->response;
    echo $response->getStatusCode();
} catch (TransportException $e) {
    // DNS, timeout, TLS: retry according to your own policy
}
```

## What maps where

| Situation | Exception |
| --- | --- |
| `{"status":"fail","message":"..."}` with HTTP 200 | `ApiException` |
| 4xx with `{"status":"fail","message":"..."}` (e.g. deleting an archived order → 403) | `ApiException` |
| HTTP 401/403 without a failure envelope, after the automatic retry | `UnexpectedStatusException` |
| HTTP 422 with `message` + `errors` | `ValidationException` |
| Any other non-2xx | `UnexpectedStatusException` |
| Invalid JSON body | `UnexpectedStatusException` |
| Login returns `status: fail` or non-2xx | `AuthenticationException` |
| Missing `clientId` that the call needs | `ValidationException` |
| PSR-18 throws | `TransportException` |

## Notes

- Binary endpoints (labels, confirmations) go through
  `ApiResponseMapper::document()`: a PDF/ZIP body becomes a `Document`, a JSON
  fail envelope becomes `ApiException`, and a JSON *success* body — the API
  saying there is no file, e.g. no scans for those AWBs — also becomes
  `ApiException`, carrying the provider's message.
- `ApiException::payload` holds the decoded envelope, so provider-specific codes
  stay available.
- The client never retries business failures. Only authentication is retried,
  once, and only when credentials are configured.
- Wrong PHP argument types raise a `TypeError`, not a `ValidationException`.
