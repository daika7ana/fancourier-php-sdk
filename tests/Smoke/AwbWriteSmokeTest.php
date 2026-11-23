<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Tests\Smoke;

use Daika7ana\FanCourier\DTOs\Awb\AwbCreationResult;
use Daika7ana\FanCourier\DTOs\Awb\InternalAwbRequest;
use Daika7ana\FanCourier\DTOs\Awb\InternalShipment;
use Daika7ana\FanCourier\DTOs\Awb\ShipmentInfo;
use Daika7ana\FanCourier\DTOs\Common\Address;
use Daika7ana\FanCourier\DTOs\Common\Contact;
use Daika7ana\FanCourier\DTOs\Common\Dimensions;
use Daika7ana\FanCourier\DTOs\Common\Packages;
use Daika7ana\FanCourier\Enums\PaymentType;
use Daika7ana\FanCourier\Enums\ServiceType;

final class AwbWriteSmokeTest extends SmokeTestCase
{
    public function testCreatesAndDeletesAwb(): void
    {
        $this->requiresWrites();
        $client = $this->clientWithClientId();

        $created = $client->awb()->createInternal(new InternalAwbRequest(shipments: [
            new InternalShipment(
                info: new ShipmentInfo(
                    service: ServiceType::Standard,
                    packages: new Packages(parcel: 1),
                    weight: 1.0,
                    payment: PaymentType::Sender,
                    dimensions: new Dimensions(length: 10.0, width: 10.0, height: 10.0),
                    observation: 'SDK smoke test - safe to delete',
                    content: 'Test',
                ),
                recipient: new Contact(
                    name: 'Test Recipient',
                    contactPerson: 'Test Person',
                    phone: '0722000000',
                    email: 'test@example.com',
                    address: new Address(
                        county: 'Cluj',
                        locality: 'Cluj-Napoca',
                        street: 'Mihai Viteazu',
                        streetNo: '1',
                        zipCode: '400001',
                        country: 'Romania',
                    ),
                ),
            ),
        ]))->first();

        $this->assertInstanceOf(AwbCreationResult::class, $created);
        $this->assertNotNull($created->awbNumber);

        try {
            $this->assertNotSame('', (string) $created->awbNumber);
        } finally {
            // a failed delete must fail the test: an AWB left in the borderou is not acceptable
            $deleted = $client->awb()->delete($created->awbNumber);

            $this->assertNotSame('', $deleted->message);
        }
    }
}
