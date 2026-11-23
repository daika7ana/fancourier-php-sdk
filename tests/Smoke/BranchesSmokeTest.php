<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Tests\Smoke;

use Daika7ana\FanCourier\DTOs\Branch\Branch;
use Daika7ana\FanCourier\DTOs\Location\County;
use Daika7ana\SimpleCollections\Collection;

final class BranchesSmokeTest extends SmokeTestCase
{
    public function testBranchesReturnsTypedCollection(): void
    {
        $branches = $this->client()->branches()->all();

        $this->assertInstanceOf(Collection::class, $branches);
        $this->assertGreaterThan(0, $branches->count());

        foreach ($branches as $branch) {
            $this->assertInstanceOf(Branch::class, $branch);
            $this->assertNotSame('', $branch->name);
        }
    }

    public function testBranchesFilteredByFirstCountyReturnTypedRows(): void
    {
        $county = $this->client()->locations()->counties()->first();

        $this->assertInstanceOf(County::class, $county);

        $branches = $this->client()->branches()->all($county->name);

        $this->assertInstanceOf(Collection::class, $branches);

        foreach ($branches as $branch) {
            $this->assertInstanceOf(Branch::class, $branch);
            $this->assertNotSame('', $branch->name);
        }
    }
}
