<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Tests\Unit\Resources;

use Daika7ana\FanCourier\DTOs\PickupPoint\PickupPoint;
use Daika7ana\FanCourier\Enums\PickupPointType;
use Daika7ana\FanCourier\Tests\Support\Fixture;
use Daika7ana\FanCourier\Tests\Support\TestClientFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class PickupPointResourceTest extends TestCase
{
    private static function pointPayload(string $id = 'FAN0033'): array
    {
        return [
            'id' => $id,
            'name' => 'FANbox Kaufland Militari',
            'routingLocation' => 'FANbox Kaufland Militari (Locker)',
            'description' => 'Langa Kaufland Grill',
            'address' => [
                'locality' => 'Bucuresti',
                'county' => 'Bucuresti',
                'street' => 'Str. Valea Cascadelor',
                'streetNo' => '3',
                'zipCode' => '61511',
                'floor' => '',
                'reference' => 'Langa Kaufland Grill',
            ],
            'latitude' => '44.43174',
            'longitude' => '26.00586',
            'schedule' => [
                ['firstHour' => '00:00', 'secondHour' => '23:59'],
            ],
            'drawer' => [
                ['type' => 'L', 'number' => 6],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function query(RequestInterface $request): array
    {
        parse_str($request->getUri()->getQuery(), $query);

        /** @var array<string, string> $query */
        return $query;
    }

    public function testAllMapsNestedAddressScheduleAndDrawer(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::ok([self::pointPayload()]),
        );

        $points = $client->pickupPoints()->all(PickupPointType::Fanbox);

        self::assertSame('/reports/pickup-points', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['type' => 'fanbox'], self::query($http->lastRequest));
        self::assertCount(1, $points);
        $point = $points->first();
        self::assertInstanceOf(PickupPoint::class, $point);
        self::assertSame('FAN0033', $point->id);
        self::assertSame('Str. Valea Cascadelor', $point->address?->street);
        self::assertSame('61511', $point->address?->zipCode);
        self::assertSame('Langa Kaufland Grill', $point->address?->reference);
        self::assertSame('44.43174', $point->latitude);
        self::assertSame('26.00586', $point->longitude);
        self::assertSame('00:00', $point->schedule[0]->firstHour);
        self::assertSame('23:59', $point->schedule[0]->secondHour);
        self::assertSame('L', $point->drawer[0]->type);
        self::assertSame(6, $point->drawer[0]->number);
    }

    public function testAllAcceptsStringType(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::ok([]),
        );

        $client->pickupPoints()->all('paypoint');

        self::assertSame(['type' => 'paypoint'], self::query($http->lastRequest));
    }

    public function testGetRequestsIdAndReturnsFirstItem(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::ok([self::pointPayload()]),
        );

        $point = $client->pickupPoints()->get('FAN0033');

        self::assertSame(['id' => 'FAN0033'], self::query($http->lastRequest));
        self::assertInstanceOf(PickupPoint::class, $point);
        self::assertSame('FAN0033', $point->id);
    }

    public function testGetIgnoresPointsWithAnotherId(): void
    {
        [$client] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::ok([self::pointPayload('OTHER')]),
        );

        self::assertNull($client->pickupPoints()->get('FAN0033'));
    }

    public function testGetMatchesAnIntegerId(): void
    {
        [$client] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::ok([self::pointPayload('12345')]),
        );

        self::assertSame('12345', $client->pickupPoints()->get('12345')?->id);
    }

    public function testGetReturnsNullOnEmptyList(): void
    {
        [$client] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::ok([]),
        );

        self::assertNull($client->pickupPoints()->get('MISSING'));
    }
}
