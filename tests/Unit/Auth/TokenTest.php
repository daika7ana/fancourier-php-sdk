<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Tests\Unit\Auth;

use Daika7ana\FanCourier\Auth\Token;
use Daika7ana\FanCourier\Exceptions\AuthenticationException;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class TokenTest extends TestCase
{
    public function testFromResponseParsesExpiresAt(): void
    {
        $token = Token::fromResponse(['token' => 'abc', 'expiresAt' => '2030-06-14 06:00:55']);

        self::assertSame('abc', $token->accessToken);
        self::assertSame('2030-06-14 06:00:55', $token->expiresAt?->format('Y-m-d H:i:s'));
        self::assertFalse($token->isExpired());
    }

    public function testTokenExpiringWithinAMinuteIsExpired(): void
    {
        $token = new Token('abc', 'Bearer', new DateTimeImmutable('+30 seconds'));

        self::assertTrue($token->isExpired());
    }

    public function testMissingTokenIsRejected(): void
    {
        $this->expectException(AuthenticationException::class);

        Token::fromResponse([]);
    }

    public function testRoundTripThroughArray(): void
    {
        $token = Token::fromArray([
            'access_token' => 'abc',
            'token_type' => 'Bearer',
            'expires_at' => '2030-06-14T06:00:55+00:00',
        ]);

        self::assertSame('abc', $token->accessToken);
        self::assertSame('2030-06-14T06:00:55+00:00', $token->toArray()['expires_at']);
    }
}
