<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Tests\Unit\Resources;

use Daika7ana\FanCourier\DTOs\Common\Dimensions;
use Daika7ana\FanCourier\DTOs\Common\OperationResult;
use Daika7ana\FanCourier\DTOs\Common\Packages;
use Daika7ana\FanCourier\DTOs\Common\PaginatedResult;
use Daika7ana\FanCourier\DTOs\Order\OrderEvent;
use Daika7ana\FanCourier\DTOs\Order\OrderInfo;
use Daika7ana\FanCourier\DTOs\Order\OrderRequest;
use Daika7ana\FanCourier\DTOs\Order\OrderSummary;
use Daika7ana\FanCourier\DTOs\Order\OrderTracking;
use Daika7ana\FanCourier\DTOs\Order\PickupHours;
use Daika7ana\FanCourier\Enums\OrderType;
use Daika7ana\FanCourier\Tests\Support\Fixture;
use Daika7ana\FanCourier\Tests\Support\TestClientFactory;
use PHPUnit\Framework\TestCase;

final class OrderResourceTest extends TestCase
{
    public function testCreateSendsJsonBodyWithClientId(): void
    {
        [$client, $httpClient] = TestClientFactory::create(
            static fn() => Fixture::json(['status' => 'success', 'data' => ['id' => 17895017]]),
        );

        $result = $client->orders()->create(new OrderRequest(
            info: new OrderInfo(
                packages: new Packages(parcel: 1, envelope: 0),
                weight: 1.0,
                dimensions: new Dimensions(length: 10, width: 10, height: 10),
                orderType: OrderType::Standard,
                pickupDate: '2023-03-23',
                pickupHours: new PickupHours(first: '09:00', second: '16:00'),
                observations: 'test',
            ),
        ));

        self::assertSame(17895017, $result->id);

        $request = $httpClient->lastRequest;
        self::assertNotNull($request);
        self::assertSame('POST', $request->getMethod());
        self::assertSame('/order', $request->getUri()->getPath());

        $body = json_decode((string) $request->getBody(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(TestClientFactory::clientId(), $body['clientId']);
        self::assertSame('Standard', $body['info']['orderType']);
        self::assertArrayNotHasKey('recipient', $body);
    }

    public function testCreateAccepts201Created(): void
    {
        [$client] = TestClientFactory::create(
            static fn() => Fixture::json(['status' => 'success', 'data' => ['id' => 27018504]], 201),
        );

        $result = $client->orders()->create(new OrderRequest(
            info: new OrderInfo(
                packages: new Packages(parcel: 2, envelope: 0),
                weight: 3.5,
                dimensions: new Dimensions(length: 20, width: 20, height: 20),
                orderType: OrderType::Standard,
            ),
        ));

        self::assertSame(27018504, $result->id);
    }

    public function testDeleteSendsClientIdAndIdQuery(): void
    {
        [$client, $httpClient] = TestClientFactory::create(
            static fn() => Fixture::json(['status' => 'success', 'data' => ['message' => 'Order deleted']]),
        );

        $result = $client->orders()->delete(3206250);

        self::assertInstanceOf(OperationResult::class, $result);
        self::assertSame('Order deleted', $result->message);
        self::assertSame('DELETE', $httpClient->lastRequest?->getMethod());
        self::assertSame(
            'clientId=' . TestClientFactory::clientId() . '&id=3206250',
            $httpClient->lastRequest?->getUri()->getQuery(),
        );
    }

    public function testReportMapsPaginationAndSummary(): void
    {
        [$client] = TestClientFactory::create(static fn() => Fixture::json([
            'status' => 'success',
            'perPage' => 1,
            'currentPage' => 1,
            'total' => 6,
            'data' => [[
                'info' => [
                    'id' => 16846879,
                    'number' => null,
                    'date' => '2023-03-21',
                    'hour' => '14:57',
                    'packages' => ['envelope' => 0, 'parcel' => 2],
                    'weight' => 3,
                    'dimensions' => ['height' => 37, 'length' => 18, 'width' => 63],
                    'pickupDate' => '2023-03-22',
                    'pickupHours' => ['firstHour' => '09:00', 'secondHour' => '13:00'],
                    'observation' => null,
                    'type' => null,
                ],
                'sender' => ['name' => 'FAN COURIER - cont test', 'contactPerson' => 'Michele Kris'],
            ]],
        ]));

        $result = $client->orders()->report('2023-03-21', page: 1, perPage: 1);

        self::assertInstanceOf(PaginatedResult::class, $result);
        self::assertSame(1, $result->perPage);
        self::assertSame(1, $result->currentPage);
        self::assertSame(6, $result->total);
        self::assertCount(1, $result->items);

        $summary = $result->items->first();
        self::assertInstanceOf(OrderSummary::class, $summary);
        self::assertSame(16846879, $summary->id);
        self::assertNull($summary->number);
        self::assertSame('14:57', $summary->hour);
        self::assertSame(2, $summary->packages->parcel);
        self::assertSame(3.0, $summary->weight);
        self::assertSame(63.0, $summary->dimensions->width);
        self::assertSame('09:00', $summary->pickupHours?->first);
        self::assertSame('FAN COURIER - cont test', $summary->sender?->name);
    }

    public function testEventsUsesConfiguredLanguageAndAllowsOverride(): void
    {
        [$client, $httpClient] = TestClientFactory::create(
            static fn() => Fixture::ok([
                ['id' => 0, 'name' => 'In asteptare'],
                ['id' => 1, 'name' => 'Plasata'],
            ]),
        );

        $events = $client->orders()->events();

        self::assertCount(2, $events);
        self::assertInstanceOf(OrderEvent::class, $events->first());
        self::assertSame(0, $events->first()->id);
        self::assertStringContainsString('language=ro', (string) $httpClient->lastRequest?->getUri()->getQuery());

        $client->orders()->events('en');

        self::assertStringContainsString('language=en', (string) $httpClient->lastRequest?->getUri()->getQuery());
    }

    public function testTrackingSendsRepeatedOrderIdParameters(): void
    {
        [$client, $httpClient] = TestClientFactory::create(static fn() => Fixture::ok([[
            'orderId' => 111,
            'orderNumber' => 'ORD-111',
            'events' => [
                ['id' => 0, 'name' => 'In asteptare', 'date' => '2023-01-03 13:29:05'],
                ['id' => 1, 'name' => 'Plasata', 'date' => '2023-01-03 13:37:29'],
            ],
        ]]));

        $tracking = $client->orders()->tracking([111, 222]);

        self::assertCount(1, $tracking);
        $entry = $tracking->first();
        self::assertInstanceOf(OrderTracking::class, $entry);
        self::assertSame(111, $entry->orderId);
        self::assertSame('ORD-111', $entry->orderNumber);
        self::assertCount(2, $entry->events);
        self::assertSame('Plasata', $entry->events[1]->name);

        $query = (string) $httpClient->lastRequest?->getUri()->getQuery();
        self::assertSame(2, substr_count($query, 'orderId%5B%5D='));
        self::assertStringContainsString('orderId%5B%5D=111', $query);
        self::assertStringContainsString('orderId%5B%5D=222', $query);
    }
}
