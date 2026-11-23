<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Tests\Unit\Exceptions;

use Daika7ana\FanCourier\Exceptions\AuthenticationException;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class AuthenticationExceptionTest extends TestCase
{
    public function testCodeDerivesFromTheResponse(): void
    {
        self::assertSame(401, (new AuthenticationException('denied', response: new Response(401)))->getCode());
        self::assertSame(0, (new AuthenticationException('denied'))->getCode());
    }

    public function testCodeFallsBackToTheArgumentWithoutAResponse(): void
    {
        self::assertSame(7, (new AuthenticationException('denied', 7))->getCode());
    }
}
