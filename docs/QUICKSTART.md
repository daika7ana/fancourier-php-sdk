# Quick Start

## 1. Create the client

```php
use Daika7ana\FanCourier\Client;
use Daika7ana\FanCourier\Config\ClientConfig;

$client = Client::create(config: new ClientConfig(
    clientId: 7032158,
    username: 'your-username',
    password: 'your-password',
));
```

Credentials in the config are used for the first login and for automatic
re-authentication. You can also log in explicitly:

```php
$client->authenticate(username: 'your-username', password: 'your-password');
```

## 2. Look up reference data

```php
$counties = $client->locations()->counties();
$localities = $client->locations()->localities('Bucuresti');
$streets = $client->locations()->streets(county: 'Bucuresti', locality: 'Bucuresti', page: 1, perPage: 50);

echo $streets->total;                 // total number of streets
echo $streets->items->first()->street;
```

PUDO points (FANbox, PayPoint, FAN office):

```php
use Daika7ana\FanCourier\Enums\PickupPointType;

$lockers = $client->pickupPoints()->all(PickupPointType::Fanbox);
$one = $client->pickupPoints()->get('FAN0033');
```

## 3. Price a shipment

```php
use Daika7ana\FanCourier\DTOs\Common\Dimensions;
use Daika7ana\FanCourier\DTOs\Common\Packages;
use Daika7ana\FanCourier\DTOs\Tariff\InternalTariffRequest;
use Daika7ana\FanCourier\Enums\PaymentType;
use Daika7ana\FanCourier\Enums\ServiceType;

$tariff = $client->awb()->internalTariff(new InternalTariffRequest(
    service: ServiceType::Standard,
    payment: PaymentType::Recipient,
    weight: 3,
    packages: new Packages(parcel: 1),
    dimensions: new Dimensions(20, 15, 10),
    recipientLocality: 'Cluj-Napoca',
    recipientCounty: 'Cluj',
));

echo $tariff->total;
```

## 4. Create an AWB

```php
use Daika7ana\FanCourier\DTOs\Awb\InternalAwbRequest;
use Daika7ana\FanCourier\DTOs\Awb\InternalShipment;
use Daika7ana\FanCourier\DTOs\Awb\ShipmentInfo;
use Daika7ana\FanCourier\DTOs\Common\Address;
use Daika7ana\FanCourier\DTOs\Common\Contact;

$result = $client->awb()->createInternal(new InternalAwbRequest(shipments: [
    new InternalShipment(
        info: new ShipmentInfo(
            service: ServiceType::Standard,
            packages: new Packages(parcel: 1),
            weight: 1,
            payment: PaymentType::Recipient,
            dimensions: new Dimensions(10, 10, 10),
            content: 'Books',
        ),
        recipient: new Contact(
            name: 'Alex Popescu',
            phone: '0723456789',
            address: new Address(
                county: 'Cluj',
                locality: 'Cluj-Napoca',
                street: 'Memorandumului',
                streetNo: '10',
                zipCode: '400114',
            ),
        ),
    ),
]));

$awb = $result->first();

echo $awb?->awbNumber;
echo $awb?->routingCode;
```

## 5. Print the label

```php
use Daika7ana\FanCourier\Enums\LabelFormat;

$number = $awb?->awbNumber ?? throw new RuntimeException('AWB creation returned no shipment.');
$label = $client->awb()->label($number, LabelFormat::A4, pdf: true);

file_put_contents($label->filename() ?? 'awb.pdf', $label->contents());
```

## 6. Track and report

```php
$tracking = $client->reports()->trackAwbs([$awb->awbNumber]);
foreach ($tracking->first()->events as $event) {
    echo $event->date . ' ' . $event->name . PHP_EOL;
}

$slip = $client->reports()->shippingSlip('2026-11-18');
$transfers = $client->reports()->bankTransfers('2026-11-18');
```
