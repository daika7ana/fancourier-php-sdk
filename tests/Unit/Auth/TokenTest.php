<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Tests\Unit\Auth;

use Daika7ana\FanCourier\Auth\Token;
use Daika7ana\FanCourier\Exceptions\AuthenticationException;
use DateTimeImmutable;
use DateTimeZone;
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

    public function testExpiresAtIsReadAsRomanianLocalTime(): void
    {
        // FAN sends "2026-11-19 14:40:03" with no offset; November is UTC+2.
        $token = Token::fromResponse(['token' => 'abc', 'expiresAt' => '2026-11-19 14:40:03']);

        self::assertSame('2026-11-19T12:40:03+00:00', $token->expiresAt?->setTimezone(new DateTimeZone('UTC'))->format(DATE_ATOM));
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
