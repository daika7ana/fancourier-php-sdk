<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Tests\Smoke;

use Daika7ana\FanCourier\DTOs\Report\AwbTracking;
use Daika7ana\FanCourier\DTOs\Report\ShippingSlipEntry;
use Daika7ana\SimpleCollections\Collection;

final class AwbTrackingSmokeTest extends SmokeTestCase
{
    public function testTrackingByKnownAwbReturnsTypedEntry(): void
    {
        $awb = $this->knownAwb();
        $tracking = $this->clientWithClientId()->reports()->trackAwbs([$awb]);

        $this->assertInstanceOf(Collection::class, $tracking);
        $this->assertCount(1, $tracking);

        $entry = $tracking->first();

        $this->assertInstanceOf(AwbTracking::class, $entry);
        $this->assertSame($awb, (string) $entry->awbNumber);

        foreach ($entry->events as $event) {
            $this->assertNotSame('', $event->name);
        }
    }

    /**
     * Prefer FANCOURIER_SMOKE_AWB; otherwise reuse an AWB from today's shipping slip.
     */
    private function knownAwb(): string
    {
        $configured = getenv('FANCOURIER_SMOKE_AWB');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        $today = (new \DateTimeImmutable())->format('Y-m-d');
        $entry = $this->clientWithClientId()->reports()->shippingSlip($today, page: 1, perPage: 10)->items->first();

        if (! $entry instanceof ShippingSlipEntry) {
            $this->markTestSkipped('No AWB today; set FANCOURIER_SMOKE_AWB to a known AWB number.');
        }

        return (string) $entry->info->awbNumber;
    }
}
