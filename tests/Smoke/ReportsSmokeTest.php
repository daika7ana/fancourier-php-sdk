<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Tests\Smoke;

use Daika7ana\FanCourier\DTOs\Common\PaginatedResult;
use Daika7ana\FanCourier\DTOs\Report\AwbEvent;
use Daika7ana\FanCourier\DTOs\Report\BankTransfer;
use Daika7ana\FanCourier\DTOs\Report\ShippingSlipEntry;
use Daika7ana\SimpleCollections\Collection;

final class ReportsSmokeTest extends SmokeTestCase
{
    public function testShippingSlipForTodayReturnsPaginatedResult(): void
    {
        $report = $this->clientWithClientId()->reports()->shippingSlip($this->today(), page: 1, perPage: 10);

        $this->assertInstanceOf(PaginatedResult::class, $report);

        foreach ($report->items as $entry) {
            $this->assertInstanceOf(ShippingSlipEntry::class, $entry);
            $this->assertNotSame('', (string) $entry->info->awbNumber);
        }
    }

    public function testAwbEventsCatalogueReturnsTypedCollection(): void
    {
        $events = $this->client()->reports()->awbEvents();

        $this->assertInstanceOf(Collection::class, $events);
        $this->assertGreaterThan(0, $events->count());

        foreach ($events as $event) {
            $this->assertInstanceOf(AwbEvent::class, $event);
            $this->assertNotSame('', $event->name);
        }
    }

    public function testBankTransfersForTodayReturnPaginatedResult(): void
    {
        $report = $this->clientWithClientId()->reports()->bankTransfers($this->today(), page: 1, perPage: 10);

        $this->assertInstanceOf(PaginatedResult::class, $report);

        foreach ($report->items as $transfer) {
            $this->assertInstanceOf(BankTransfer::class, $transfer);
        }
    }

    private function today(): string
    {
        return (new \DateTimeImmutable())->format('Y-m-d');
    }
}
