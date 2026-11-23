<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Tests\Smoke;

use Daika7ana\FanCourier\DTOs\PickupPoint\PickupPoint;
use Daika7ana\FanCourier\Enums\PickupPointType;
use Daika7ana\SimpleCollections\Collection;

final class PickupPointsSmokeTest extends SmokeTestCase
{
    public function testFanboxPointsReturnTypedCollection(): void
    {
        $points = $this->client()->pickupPoints()->all(PickupPointType::Fanbox);

        $this->assertInstanceOf(Collection::class, $points);
        $this->assertGreaterThan(0, $points->count());

        foreach ($points as $point) {
            $this->assertInstanceOf(PickupPoint::class, $point);
            $this->assertNotSame('', $point->name);
        }
    }

    public function testPickupPointCanBeFetchedById(): void
    {
        $points = $this->client()->pickupPoints()->all(PickupPointType::Fanbox);
        $first = $points->first();

        if (! $first instanceof PickupPoint) {
            $this->markTestSkipped('The API returned no FANbox points to look up by id.');
        }

        $point = $this->client()->pickupPoints()->get((string) $first->id);

        $this->assertInstanceOf(PickupPoint::class, $point);
        $this->assertSame((string) $first->id, (string) $point->id);
    }
}
