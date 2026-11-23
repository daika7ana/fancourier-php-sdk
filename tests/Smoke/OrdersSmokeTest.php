<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Tests\Smoke;

use Daika7ana\FanCourier\DTOs\Common\PaginatedResult;
use Daika7ana\FanCourier\DTOs\Order\OrderEvent;
use Daika7ana\FanCourier\DTOs\Order\OrderSummary;
use Daika7ana\FanCourier\DTOs\Order\OrderTracking;
use Daika7ana\SimpleCollections\Collection;

final class OrdersSmokeTest extends SmokeTestCase
{
    public function testOrderReportForTodayReturnsPaginatedResult(): void
    {
        $report = $this->clientWithClientId()->orders()->report($this->today(), page: 1, perPage: 5);

        $this->assertInstanceOf(PaginatedResult::class, $report);

        foreach ($report->items as $summary) {
            $this->assertInstanceOf(OrderSummary::class, $summary);
            $this->assertNotSame(0, $summary->id);
        }
    }

    public function testOrderTrackingForFirstReportedOrderReturnsTypedEntry(): void
    {
        $client = $this->clientWithClientId();
        $summary = $client->orders()->report($this->today(), page: 1, perPage: 5)->items->first();

        if (! $summary instanceof OrderSummary) {
            $this->markTestSkipped('No order was placed today, so there is no order id to track.');
        }

        $tracking = $client->orders()->tracking([$summary->id]);

        $this->assertInstanceOf(Collection::class, $tracking);
        $this->assertCount(1, $tracking);

        $entry = $tracking->first();

        $this->assertInstanceOf(OrderTracking::class, $entry);
        $this->assertSame((string) $summary->id, (string) $entry->orderId);
    }

    public function testOrderEventsCatalogueReturnsTypedCollection(): void
    {
        $events = $this->client()->orders()->events();

        $this->assertInstanceOf(Collection::class, $events);
        $this->assertGreaterThan(0, $events->count());

        foreach ($events as $event) {
            $this->assertInstanceOf(OrderEvent::class, $event);
            $this->assertNotSame('', $event->name);
        }
    }

    private function today(): string
    {
        return (new \DateTimeImmutable())->format('Y-m-d');
    }
}
