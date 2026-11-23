<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Tests\Unit\Exceptions;

use Daika7ana\FanCourier\Exceptions\ValidationException;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class ValidationExceptionTest extends TestCase
{
    public function testCodeDerivesFromTheResponse(): void
    {
        self::assertSame(422, (new ValidationException([], response: new Response(422)))->getCode());
        self::assertSame(400, (new ValidationException([], 'Bad request', response: new Response(400)))->getCode());
        self::assertSame(0, (new ValidationException([]))->getCode());
    }

    public function testCodeFallsBackToTheArgumentWithoutAResponse(): void
    {
        $exception = new ValidationException([], 'Validation failed', 7);

        self::assertSame(7, $exception->getCode());
        self::assertSame('Validation failed', $exception->getMessage());
    }
}
