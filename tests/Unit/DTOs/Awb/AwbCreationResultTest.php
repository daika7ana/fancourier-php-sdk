<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Tests\Unit\DTOs\Awb;

use Daika7ana\FanCourier\DTOs\Awb\AwbCreationResult;
use PHPUnit\Framework\TestCase;

final class AwbCreationResultTest extends TestCase
{
    public function testFailedShipmentKeepsItsErrorsAndNoNumber(): void
    {
        $result = AwbCreationResult::fromArray([
            'awbNumber' => null,
            'errors' => ['recipient' => ['Invalid phone']],
        ]);

        self::assertNull($result->awbNumber);
        self::assertSame(['recipient' => ['Invalid phone']], $result->errors);
    }

    public function testStringErrorsAreKept(): void
    {
        self::assertSame('nope', AwbCreationResult::fromArray(['errors' => 'nope'])->errors);
    }
}
