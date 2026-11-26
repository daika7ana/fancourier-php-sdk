<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Tests\Unit\Resources;

use Daika7ana\FanCourier\DTOs\Awb\ExternalAwbRequest;
use Daika7ana\FanCourier\DTOs\Awb\ExternalShipment;
use Daika7ana\FanCourier\DTOs\Awb\ExternalShipmentInfo;
use Daika7ana\FanCourier\DTOs\Awb\InternalAwbRequest;
use Daika7ana\FanCourier\DTOs\Awb\InternalShipment;
use Daika7ana\FanCourier\DTOs\Awb\ShipmentInfo;
use Daika7ana\FanCourier\DTOs\Common\Contact;
use Daika7ana\FanCourier\DTOs\Common\Dimensions;
use Daika7ana\FanCourier\DTOs\Common\Packages;
use Daika7ana\FanCourier\DTOs\Tariff\InternalTariffRequest;
use Daika7ana\FanCourier\Enums\ContentType;
use Daika7ana\FanCourier\Enums\DeliveryMode;
use Daika7ana\FanCourier\Enums\LabelFormat;
use Daika7ana\FanCourier\Enums\PaymentType;
use Daika7ana\FanCourier\Enums\ServiceType;
use Daika7ana\FanCourier\Exceptions\UnexpectedStatusException;
use Daika7ana\FanCourier\Tests\Support\Fixture;
use Daika7ana\FanCourier\Tests\Support\TestClientFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class AwbResourceTest extends TestCase
{
    public function testCreateInternalSendsBodyAndMapsResponse(): void
    {
        [$client, $http] = TestClientFactory::create(static fn() => Fixture::json([
            'response' => [[
                'awbNumber' => 2233300120284,
                'tariff' => 29.7,
                'vat' => 5.64,
                'packages' => 1,
                'letter' => 'B',
                'routingCode' => '1181',
                'office' => 'Bucuresti',
                'pickUpPointId' => null,
                'visualCode' => '11-08-01',
                'estimatedDeliveryTime' => 24,
                'errors' => null,
            ]],
        ]));

        $result = $client->awb()->createInternal($this->internalRequest());

        $request = $http->lastRequest;
        self::assertNotNull($request);
        self::assertSame('POST', $request->getMethod());
        self::assertSame('/intern-awb', $request->getUri()->getPath());

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('7032158', (string) $body['clientId']);
        self::assertSame('Standard', $body['shipments'][0]['info']['service']);

        $created = $result->first();
        self::assertNotNull($created);
        self::assertSame(2233300120284, $created->awbNumber);
        self::assertSame(29.7, $created->tariff);
        self::assertSame(5.64, $created->vat);
        self::assertSame(1, $created->packages);
    }

    public function testCreateExternalSendsDeliveryModeAndContentType(): void
    {
        // /extern-awb answers with a bare list, unlike /intern-awb's {"response": [...]}.
        [$client, $http] = TestClientFactory::create(static fn() => Fixture::json([
            ['awbNumber' => '7000044743247', 'packages' => 1],
        ]));

        $result = $client->awb()->createExternal(new ExternalAwbRequest([
            new ExternalShipment(
                info: new ExternalShipmentInfo(
                    service: ServiceType::Export,
                    deliveryMode: DeliveryMode::Air,
                    contentType: ContentType::Document,
                    packages: new Packages(1, 0),
                    dimensions: new Dimensions(1, 1, 1),
                    weight: 5.0,
                ),
                recipient: Contact::fromArray([
                    'name' => 'Recipient',
                    'phone' => '0723456789',
                    'address' => [
                        'country' => 'Bulgaria',
                        'region' => 'Sofia',
                        'locality' => 'Sofia',
                        'street' => 'ul. Ivan Denkoglu',
                        'streetNo' => '17',
                        'zipCode' => '1000',
                    ],
                ]),
            ),
        ]));

        $request = $http->lastRequest;
        self::assertNotNull($request);
        self::assertSame('/extern-awb', $request->getUri()->getPath());

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('aerian', $body['shipments'][0]['info']['deliveryMode']);
        self::assertSame('document', $body['shipments'][0]['info']['contentType']);
        self::assertSame('Bulgaria', $body['shipments'][0]['recipient']['address']['country']);
        self::assertSame('7000044743247', $result->first()?->awbNumber);
    }

    public function testLabelRejectsJsonFailEnvelope(): void
    {
        [$client] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::fail('Invalid AWB'),
        );

        $this->expectException(UnexpectedStatusException::class);
        $this->expectExceptionMessage('Invalid AWB');

        $client->awb()->label('7000044743247', pdf: true);
    }

    public function testDeleteSendsMethodAndQuery(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn() => Fixture::ok('AWB-ul a fost sters cu succes'),
        );

        $result = $client->awb()->delete('2081300120386');

        $request = $http->lastRequest;
        self::assertNotNull($request);
        self::assertSame('DELETE', $request->getMethod());
        self::assertSame('/awb', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);

        self::assertSame('7032158', $query['clientId']);
        self::assertSame('2081300120386', $query['awb']);
        self::assertSame('AWB-ul a fost sters cu succes', $result->message);
    }

    public function testLabelSendsRepeatedAwbsAndReturnsDocument(): void
    {
        [$client, $http] = TestClientFactory::create(static fn() => Fixture::raw(
            '%PDF-1.4 fake',
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="labels.pdf"',
            ],
        ));

        $document = $client->awb()->label(
            ['2080300121976', '2081300120386'],
            LabelFormat::A4,
            pdf: true,
        );

        $request = $http->lastRequest;
        self::assertNotNull($request);
        self::assertSame('/awb/label', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);

        self::assertSame('7032158', $query['clientId']);
        self::assertSame(['2080300121976', '2081300120386'], $query['awbs']);
        self::assertSame('A4', $query['format']);
        self::assertSame('1', $query['pdf']);

        self::assertSame('application/pdf', $document->contentType);
        self::assertSame('%PDF-1.4 fake', $document->contents());
        self::assertSame('%PDF-1.4 fake', $document->stream->getContents());
        self::assertSame('labels.pdf', $document->filename());
    }

    public function testInternalTariffUsesBracketQueryAndParsesTotals(): void
    {
        [$client, $http] = TestClientFactory::create(static fn() => Fixture::ok([
            'extraKmCost' => 0,
            'weightCost' => 24.5,
            'insuranceCost' => 0,
            'optionsCost' => 0,
            'fuelCost' => 1.69,
            'costNoVAT' => 26.19,
            'vat' => 4.98,
            'total' => 31.17,
        ]));

        $tariff = $client->awb()->internalTariff(new InternalTariffRequest(
            service: ServiceType::ContColector,
            payment: PaymentType::Sender,
            weight: 2.0,
            packages: new Packages(1, 0),
            recipientLocality: 'Constanta',
            recipientCounty: 'Constanta',
            dimensions: new Dimensions(7, 6, 5),
        ));

        $request = $http->lastRequest;
        self::assertNotNull($request);
        self::assertSame('/reports/awb/internal-tariff', $request->getUri()->getPath());

        $decoded = urldecode($request->getUri()->getQuery());

        self::assertStringContainsString('info[service]=Cont Colector', $decoded);
        self::assertStringContainsString('info[weight]=2', $decoded);
        self::assertStringContainsString('info[packages][parcel]=1', $decoded);
        self::assertStringContainsString('info[dimensions][length]=7', $decoded);
        self::assertStringContainsString('recipient[locality]=Constanta', $decoded);
        self::assertStringContainsString('clientId=7032158', $decoded);

        self::assertSame(31.17, $tariff->total);
        self::assertSame(24.5, $tariff->weightCost);
        self::assertSame(4.98, $tariff->vat);
    }

    /**
     * @return iterable<string, array{string, ResponseInterface, string}>
     */
    public static function malformedTariff(): iterable
    {
        $noTariff = 'FAN Courier returned no tariff for this request.';

        yield 'internal, missing data' => ['internalTariff', Fixture::json(['status' => 'success']), $noTariff];
        yield 'internal, empty data' => ['internalTariff', Fixture::ok([]), $noTariff];
        yield 'internal, non-array data' => ['internalTariff', Fixture::ok(42), $noTariff];
        yield 'internal, string data' => ['internalTariff', Fixture::ok('no tariff'), 'no tariff'];
        yield 'external, missing data' => ['externalTariff', Fixture::json(['status' => 'success']), $noTariff];
        yield 'external, empty data' => ['externalTariff', Fixture::ok([]), $noTariff];
        yield 'external, non-array data' => ['externalTariff', Fixture::ok(42), $noTariff];
        yield 'external, string data' => ['externalTariff', Fixture::ok('no tariff'), 'no tariff'];
    }

    #[DataProvider('malformedTariff')]
    public function testTariffRejectsMissingOrEmptyData(string $method, ResponseInterface $response, string $message): void
    {
        [$client] = TestClientFactory::create(static fn() => $response);

        $this->expectException(UnexpectedStatusException::class);
        $this->expectExceptionMessage($message);

        $client->awb()->{$method}([]);
    }

    public function testFailureSurfacesAsUnexpectedStatusException(): void
    {
        [$client] = TestClientFactory::create(static fn() => Fixture::fail('AWB could not be created'));

        $this->expectException(UnexpectedStatusException::class);
        $this->expectExceptionMessage('AWB could not be created');

        $client->awb()->createInternal($this->internalRequest());
    }

    private function internalRequest(): InternalAwbRequest
    {
        return new InternalAwbRequest([
            new InternalShipment(
                info: new ShipmentInfo(
                    service: ServiceType::Standard,
                    packages: new Packages(1, 0),
                    weight: 2.0,
                    payment: PaymentType::Sender,
                    dimensions: new Dimensions(1, 2, 3),
                ),
                recipient: Contact::fromArray([
                    'name' => 'Nume Destinatar',
                    'phone' => '0723456789',
                    'address' => [
                        'county' => 'Bucuresti',
                        'locality' => 'Bucuresti',
                        'street' => 'Fabrica de Glucoza',
                        'streetNo' => '11C',
                        'zipCode' => '020331',
                    ],
                ]),
            ),
        ]);
    }
}
