# DTOs

DTOs are `final readonly` classes. Response DTOs expose `fromArray()`; request
DTOs also expose `toArray()`, which drops every `null` optional so the payload
only contains what you set.

Identifiers (`id`, `awbNumber`, order ids) are typed `int|string` and keep the
wire type: `11` stays an `int`, `"11"` stays a string. Nothing is coerced.

## Request DTOs

### AWB (domestic)

- `DTOs\Awb\InternalAwbRequest(shipments: list<InternalShipment>)`
- `DTOs\Awb\InternalShipment(info: ShipmentInfo, recipient: Contact, sender?: Contact)`
- `DTOs\Awb\ShipmentInfo` — required: `service`, `packages`, `weight`, `payment`,
  `dimensions`; optional: `bank`, `bankAccount`, `cod`, `declaredValue`, `refund`,
  `returnPayment`, `observation`, `content`, `costCenter`, `options[]`, `uitCode`,
  and the non-EU block `isValueUnderThreshold`, `countryCode`, `vatId`, `company`.

```php
new ShipmentInfo(
    service: ServiceType::Standard,
    packages: new Packages(parcel: 1),
    weight: 1.5,
    payment: PaymentType::Recipient,
    dimensions: new Dimensions(20, 15, 10),
    options: [AwbOption::SaturdayDelivery],
)
```

FANbox/PayPoint delivery needs the matching service (`ServiceType::Fanbox`,
`ServiceType::FanboxContColector`, `ServiceType::CollectPoint` or
`ServiceType::CollectPointContColector`), the option letter (`V`/`W` for FANbox,
`E`/`F` for PayPoint) and `pickupLocationId`/`dropOffLocationId` on the address.
The address county and locality must match the point:

```php
new Address(
    county: 'Cluj',
    locality: 'Cluj-Napoca',
    street: 'Memorandumului',
    streetNo: '10',
    pickupLocationId: 'FAN0033',
);
```

### AWB (export)

- `DTOs\Awb\ExternalAwbRequest(shipments: list<ExternalShipment>)`
- `DTOs\Awb\ExternalShipment(info: ExternalShipmentInfo, recipient: Contact, sender?: Contact)`
- `DTOs\Awb\ExternalShipmentInfo` — required: `service`, `deliveryMode`,
  `contentType`, `packages`, `dimensions`, `weight`; optional: `payment`, `bank`,
  `bankAccount`, `cod`, `declaredValue`, `observation`, `costCenter`, `options[]`,
  `uitCode`. The recipient address uses `country`, `region`, `locality`, `street`,
  `streetNo`, `zipCode`.

### Tariffs

- `DTOs\Tariff\InternalTariffRequest` — `service`, `payment`, `weight`,
  `packages`, plus `recipientLocality`, `recipientCounty`, `options[]`,
  `dimensions`, `declaredValue`, `senderLocality`, `senderCounty`.
- `DTOs\Tariff\ExternalTariffRequest` — `service`, `deliveryMode`, `documentType`,
  `weight`, `dimensions`, `packages`, plus `recipientCountry`, `senderLocality`,
  `senderCounty`.

### Orders

- `DTOs\Order\OrderRequest(info: OrderInfo, recipient?: Contact)`
- `DTOs\Order\OrderInfo(packages, weight, dimensions, orderType, pickupDate?,
  pickupHours?, observations?)`
- `DTOs\Order\PickupHours(first: '09:00', second: '16:00')` — `fromArray()`
  accepts both the request (`first`/`second`) and the report
  (`firstHour`/`secondHour`) spellings.

## Response DTOs

| DTO | Contents |
| --- | --- |
| `DTOs\Awb\AwbResult` | `results: list<AwbCreationResult>`, `rawData`, `first()` |
| `DTOs\Awb\AwbCreationResult` | `awbNumber`, `tariff`, `vat`, `packages`, `letter`, `routingCode`, `office`, `pickUpPointId`, `visualCode`, `estimatedDeliveryTime`, `errors` |
| `DTOs\Tariff\Tariff` | `extraKmCost`, `weightCost`, `insuranceCost`, `optionsCost`, `fuelCost`, `costNoVAT`, `vat`, `total` |
| `DTOs\Service\Service` | `id`, `name`, `description` |
| `DTOs\Service\ServiceOption` | `id` (option letter), `name` |
| `DTOs\Location\*` | `County`, `Locality`, `Street` (+`StreetDetails`), `Country`, `ExternalCounty`, `ExternalLocality` |
| `DTOs\PickupPoint\PickupPoint` | `id`, `name`, `routingLocation`, `description`, `address`, `latitude`, `longitude`, `schedule[]`, `drawer[]` |
| `DTOs\PickupPoint\PickupSchedule` | `firstHour`, `secondHour` |
| `DTOs\PickupPoint\PickupDrawer` | `type`, `number` |
| `DTOs\Branch\Branch` | `id`, `name`, bank/contact fields, `address` |
| `DTOs\Order\OrderResult` | `id` |
| `DTOs\Order\OrderSummary` | `id`, `number`, `date`, `hour`, `packages`, `weight`, `dimensions`, `pickupDate`, `pickupHours`, `observation`, `type`, `sender` |
| `DTOs\Order\OrderEvent` | `id`, `name`, `date` |
| `DTOs\Order\OrderTracking` | `orderId`, `orderNumber`, `events[]` |
| `DTOs\Report\AwbInfo` | AWB number, weight, dimensions, payment, cod, declared value, content, date, service, cost, costCenter, options, refund, currency |
| `DTOs\Report\ShippingSlipEntry` | `info`, `recipient`, `sender` |
| `DTOs\Report\AwbTracking` | `awbNumber`, `date`, `paymentDate`, return/redirection/reimbursement/oPOD numbers, `confirmation`, `otd`, `events[]` |
| `DTOs\Report\AwbEvent` | `id`, `name`, `location`, `date` |
| `DTOs\Report\BankTransfer` | `awbNumber`, `awbDate`, `amountCollected`, `content`, `transferDate`, return/reimbursement numbers, `transactionDate`, recipient/sender names |
| `DTOs\Report\Confirmation` | `name`, `date` |

## Common DTOs

- `DTOs\Common\Dimensions(length, width, height)` — numeric strings are cast.
- `DTOs\Common\Packages(parcel, envelope)` — accepts the legacy `envelopes` key.
- `DTOs\Common\Address` — domestic and export fields in one class; `fromArray()`
  accepts lowercase `zipcode`, `toArray()` omits empty values.
- `DTOs\Common\Contact` — every field optional: `name`, `contactPerson`, `phone`,
  `secondaryPhone`, `email`, `address`.
- `DTOs\Common\Document` — `stream`, `contentType`, `contentDisposition`,
  `contents()`, `filename()`.
- `DTOs\Common\OperationResult` — `message`, `rawData` for delete/cancel replies.
- `DTOs\Common\Collection` — iterable, plus `first/last/get/has/filter/reject/
  only/except/map/mapWithKeys/pluck/push/prepend/each/toArray`.
- `DTOs\Common\PaginatedResult` — `items`, `perPage`, `currentPage`, `total`.

## Enums

| Enum | Values |
| --- | --- |
| `ServiceType` | 24 FAN Courier service strings (`Standard`, `RedCode`, `Cont Colector`, `Express Loco 1H` … `FANbox Cont Colector`) |
| `AwbOption` | `A`–`F`, `M`, `O`, `P`, `S`, `V`, `W`, `X`, `Y` — semantic case names |
| `PaymentType` | `Sender` = `expeditor`, `Recipient` = `destinatar`, `Other` = `Altul` |
| `DeliveryMode` | `Road` = `rutier`, `Air` = `aerian` |
| `ContentType` | `Document` = `document`, `NonDocument` = `non document` |
| `PickupPointType` | `Fanbox`, `PayPoint`, `Office` |
| `OrderType` | `Standard`, `ExpressLoco1H/2H/4H/6H` |
| `LabelFormat` | `A4`, `A5`, `A6` |

Every enum-typed property and parameter also accepts the raw API string.
