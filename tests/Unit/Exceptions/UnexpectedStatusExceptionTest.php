<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Tests\Unit\Exceptions;

use Daika7ana\FanCourier\DTOs\Common\Errors\FanCourierError;
use Daika7ana\FanCourier\Exceptions\UnexpectedStatusException;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class UnexpectedStatusExceptionTest extends TestCase
{
    public function testAcceptsANullResponse(): void
    {
        $exception = new UnexpectedStatusException();

        self::assertNull($exception->response);
        self::assertNull($exception->retryAfter);
        self::assertNull($exception->error);
        self::assertSame('Unexpected HTTP status', $exception->getMessage());
    }

    public function testCodeDerivesFromTheResponse(): void
    {
        self::assertSame(503, (new UnexpectedStatusException(new Response(503)))->getCode());
        self::assertSame(0, (new UnexpectedStatusException())->getCode());
        self::assertSame(9, (new UnexpectedStatusException(code: 9))->getCode());
    }

    public function testDerivesTheMessageFromTheResponseAndCarriesTheError(): void
    {
        $error = FanCourierError::fromArray(['message' => 'down'], 503);
        $exception = new UnexpectedStatusException(new Response(503), retryAfter: 30, error: $error);

        self::assertSame('Unexpected HTTP status 503', $exception->getMessage());
        self::assertSame(30, $exception->retryAfter);
        self::assertSame($error, $exception->error);
    }
}
