<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Tests\Unit\DTOs;

use Daika7ana\FanCourier\DTOs\Awb\AwbCreationResult;
use Daika7ana\FanCourier\DTOs\Location\County;
use Daika7ana\FanCourier\DTOs\Order\OrderEvent;
use PHPUnit\Framework\TestCase;

final class IdTypeTest extends TestCase
{
    public function testNumericIdsKeepTheirWireType(): void
    {
        $fromInt = County::fromArray(['id' => 11, 'name' => 'Bucuresti']);
        $fromString = County::fromArray(['id' => '11', 'name' => 'Bucuresti']);

        self::assertSame(11, $fromInt->id);
        self::assertSame('11', $fromString->id);
    }

    public function testEventIdsMayBeStringsOrIntegers(): void
    {
        self::assertSame('C0', OrderEvent::fromArray(['id' => 'C0'])->id);
        self::assertSame(0, OrderEvent::fromArray(['id' => 0])->id);
    }

    public function testAwbNumbersKeepTheirWireType(): void
    {
        self::assertSame('7000044743247', AwbCreationResult::fromArray(['awbNumber' => '7000044743247'])->awbNumber);
        self::assertSame(7000044743247, AwbCreationResult::fromArray(['awbNumber' => 7000044743247])->awbNumber);
    }
}
