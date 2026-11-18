<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Tests\Unit\DTOs\Awb;

use Daika7ana\FanCourier\DTOs\Awb\InternalAwbRequest;
use Daika7ana\FanCourier\DTOs\Awb\InternalShipment;
use Daika7ana\FanCourier\DTOs\Awb\ShipmentInfo;
use Daika7ana\FanCourier\DTOs\Common\Contact;
use Daika7ana\FanCourier\DTOs\Common\Dimensions;
use Daika7ana\FanCourier\DTOs\Common\Packages;
use Daika7ana\FanCourier\Enums\AwbOption;
use Daika7ana\FanCourier\Enums\PaymentType;
use Daika7ana\FanCourier\Enums\ServiceType;
use PHPUnit\Framework\TestCase;

final class InternalAwbRequestTest extends TestCase
{
    public function testNormalizesEnumsAndOmitsUnsetOptionalKeys(): void
    {
        $request = new InternalAwbRequest([
            new InternalShipment(
                info: new ShipmentInfo(
                    service: ServiceType::Fanbox,
                    packages: new Packages(1, 0),
                    weight: 2.0,
                    payment: PaymentType::Sender,
                    dimensions: new Dimensions(3, 4, 6),
                    options: [AwbOption::EPod],
                ),
                recipient: Contact::fromArray([
                    'name' => 'Alex',
                    'phone' => '0723456789',
                    'address' => [
                        'county' => 'Bucuresti',
                        'locality' => 'Bucuresti',
                    ],
                ]),
            ),
        ]);

        $data = $request->toArray();
        $shipment = $data['shipments'][0];

        self::assertArrayNotHasKey('clientId', $data);
        self::assertSame('FANbox', $shipment['info']['service']);
        self::assertSame('expeditor', $shipment['info']['payment']);
        self::assertSame(['X'], $shipment['info']['options']);
        self::assertSame(3.0, $shipment['info']['dimensions']['length']);

        self::assertArrayNotHasKey('refund', $shipment['info']);
        self::assertArrayNotHasKey('bank', $shipment['info']);
        self::assertArrayNotHasKey('costCenter', $shipment['info']);
        self::assertArrayNotHasKey('isValueUnderThreshold', $shipment['info']);
    }

    public function testClientIdBelongsToTheClientNotTheRequest(): void
    {
        $request = InternalAwbRequest::fromArray(['shipments' => [], 'clientId' => 1234]);

        self::assertArrayNotHasKey('clientId', $request->toArray());
    }

    public function testPassesPhoneAndCountryThrough(): void
    {
        $shipment = new InternalShipment(
            info: new ShipmentInfo(
                service: 'FANbox',
                packages: new Packages(1, 0),
                weight: 1.0,
                payment: 'expeditor',
                dimensions: new Dimensions(1, 1, 1),
            ),
            recipient: Contact::fromArray([
                'name' => 'Alex',
                'phone' => '0723456789',
                'address' => [
                    'county' => 'Bucuresti',
                    'locality' => 'Bucuresti',
                    'country' => 'Romania',
                ],
            ]),
        );

        $data = $shipment->toArray();

        self::assertSame('0723456789', $data['recipient']['phone']);
        self::assertSame('Romania', $data['recipient']['address']['country']);
    }
}
