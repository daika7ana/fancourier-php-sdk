# Resources

All endpoints live on `Client`. Resources are created lazily and can be used
directly:

```php
$client->awb()->createInternal($request);
```

`createInternal()`, `createExternal()`, `internalTariff()`, `externalTariff()` and
`orders()->create()` accept either a typed request DTO or a raw array of the same
shape; every other method takes plain arguments. Responses are always typed DTOs.
`Collection` (`Daika7ana\SimpleCollections\Collection`) is iterable (`foreach`,
`count()`, `first()`, `last()`, `filter()`, `map()`, `pluck()`, …);
`PaginatedResult` exposes `items`, `perPage`, `currentPage` and `total`.

Endpoint reference: [RO_FANCourier_API_090126.pdf](RO_FANCourier_API_090126.pdf).

> The PDF is FAN Courier's document, included unmodified for reference and not
> covered by this project's license. Credentials in its examples belong to FAN
> Courier, not to this SDK; they grant no access and are not authorized for use
> here. See the notice in the README.

## services()

| Method | Endpoint | Returns |
| --- | --- | --- |
| `all()` | `GET /reports/services` | `Collection<int, DTOs\Service\Service>` |
| `options(ServiceType\|string $service)` | `GET /reports/service-options` | `Collection<int, DTOs\Service\ServiceOption>` |

```php
foreach ($client->services()->all() as $service) {
    echo $service->id . ': ' . $service->name;
}

$options = $client->services()->options(ServiceType::Fanbox);
```

## awb()

| Method | Endpoint | Returns |
| --- | --- | --- |
| `createInternal(InternalAwbRequest\|array)` | `POST /intern-awb` | `DTOs\Awb\AwbResult` |
| `createExternal(ExternalAwbRequest\|array)` | `POST /extern-awb` | `DTOs\Awb\AwbResult` |
| `delete(int\|string $awb)` | `DELETE /awb` | `DTOs\Common\OperationResult` |
| `label(int\|string\|array $awbs, LabelFormat\|string\|null $format, ?bool $pdf, ?bool $zpl, ?int $dpi, ?string $language)` | `GET /awb/label` | `DTOs\Common\Document` |
| `internalTariff(InternalTariffRequest\|array)` | `GET /reports/awb/internal-tariff` | `DTOs\Tariff\Tariff` |
| `externalTariff(ExternalTariffRequest\|array)` | `GET /reports/awb/external-tariff` | `DTOs\Tariff\Tariff` |

`createInternal()` and `createExternal()` always fill `clientId` from the client
config. The result is a list because one request may create several AWBs:

```php
$result = $client->awb()->createInternal($request);

$result->first()?->awbNumber;   // int|string|null
$result->results;               // list<AwbCreationResult>
$result->rawData;               // decoded response
```

Labels are binary; `Document` gives you the bytes and download metadata:

```php
$label = $client->awb()->label(['2233300120284'], LabelFormat::A4, pdf: true, dpi: 300);

$label->contents();    // raw PDF bytes
$label->contentType;   // application/pdf
$label->filename();    // from Content-Disposition, when present
```

## locations()

| Method | Endpoint | Returns |
| --- | --- | --- |
| `counties()` | `GET /reports/counties` | `Collection<int, DTOs\Location\County>` |
| `localities(?string $county)` | `GET /reports/localities` | `Collection<int, DTOs\Location\Locality>` |
| `streets(?county, ?locality, ?page, ?perPage ≤ 1000)` | `GET /reports/streets` | `PaginatedResult<DTOs\Location\Street>` |
| `countries()` | `GET /reports/countries` | `Collection<int, DTOs\Location\Country>` |
| `externalCounties(?string $country)` | `GET /reports/external-counties` | `Collection<int, DTOs\Location\ExternalCounty>` |
| `externalLocalities(?country, ?county, ?page, ?perPage)` | `GET /reports/external-localities` | `PaginatedResult<DTOs\Location\ExternalLocality>` |

```php
$streets = $client->locations()->streets(county: 'Cluj', locality: 'Cluj-Napoca', perPage: 100);

foreach ($streets->items as $street) {
    echo $street->street . ' ' . $street->details?->zipCode;
}
```

## pickupPoints()

| Method | Endpoint | Returns |
| --- | --- | --- |
| `all(PickupPointType\|string $type)` | `GET /reports/pickup-points` | `Collection<int, DTOs\PickupPoint\PickupPoint>` |
| `get(string $id)` | `GET /reports/pickup-points` | `DTOs\PickupPoint\PickupPoint\|null` |

`$type` is `fanbox`, `paypoint` or `office` (`Enums\PickupPointType`). Each point
carries its address, coordinates (as strings), opening `schedule` and locker
`drawer` list.

## branches()

```php
$branches = $client->branches()->all(county: 'Cluj', locality: 'Cluj-Napoca');
```

`GET /reports/branches` → `Collection<int, DTOs\Branch\Branch>` with bank,
contact and address details.

## orders()

| Method | Endpoint | Returns |
| --- | --- | --- |
| `create(OrderRequest\|array)` | `POST /order` (answers 201) | `DTOs\Order\OrderResult` |
| `delete(int\|string $id)` | `DELETE /order` | `DTOs\Common\OperationResult` |
| `report(string $date, ?page, ?perPage)` | `GET /reports/orders` | `PaginatedResult<DTOs\Order\OrderSummary>` |
| `events(?string $language)` | `GET /reports/order-events` | `Collection<int, DTOs\Order\OrderEvent>` |
| `tracking(array $orderIds, ?string $language)` | `GET /reports/orders/tracking` | `Collection<int, DTOs\Order\OrderTracking>` |

```php
use Daika7ana\FanCourier\DTOs\Order\OrderRequest;

$order = $client->orders()->create($request);

echo $order->id;
```

`OrderType::Standard` needs no recipient; the `Express Loco` types require one.

## reports()

| Method | Endpoint | Returns |
| --- | --- | --- |
| `shippingSlip(string $date, ?page, ?perPage ≤ 100)` | `GET /reports/awb` | `PaginatedResult<DTOs\Report\ShippingSlipEntry>` |
| `awbEvents(?string $language)` | `GET /reports/awb-events` | `Collection<int, DTOs\Report\AwbEvent>` |
| `trackAwbs(array $awbs, ?string $language)` | `GET /reports/awb/tracking` | `Collection<int, DTOs\Report\AwbTracking>` |
| `bankTransfers(string $date, ?page, ?perPage)` | `GET /reports/bank-transfers` | `PaginatedResult<DTOs\Report\BankTransfer>` |
| `confirmations(array $awbs)` | `GET /reports/get-awb-confirmations` | `DTOs\Common\Document` (ZIP); throws `ApiException` when the API has no scans for those AWBs |

`$date` is passed to the API unchanged; FAN Courier uses `YYYY-MM-DD` for the
AWB reports and `DD-MM-YYYY` in some order examples.

```php
$tracking = $client->reports()->trackAwbs(['2233300120284'], language: 'en');

foreach ($tracking->first()->events as $event) {
    echo $event->location . ' ' . $event->name;
}

$zip = $client->reports()->confirmations(['2233300120284']);
file_put_contents('confirmations.zip', $zip->contents());
```
