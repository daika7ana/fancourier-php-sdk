<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Tests\Unit\Auth;

use Daika7ana\FanCourier\Auth\CredentialsAuthenticator;
use Daika7ana\FanCourier\Config\ClientConfig;
use Daika7ana\FanCourier\Exceptions\AuthenticationException;
use Daika7ana\FanCourier\Tests\Support\FakeHttpClient;
use Daika7ana\FanCourier\Tests\Support\Fixture;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\TestCase;

final class CredentialsAuthenticatorTest extends TestCase
{
    public function testSuccessfulLoginReturnsToken(): void
    {
        $httpClient = new FakeHttpClient(static fn() => Fixture::ok([
            'token' => '48980806|abc',
            'expiresAt' => '2030-06-14 06:00:55',
        ]));
        $factory = new HttpFactory();

        $token = (new CredentialsAuthenticator($httpClient, $factory, new ClientConfig()))
            ->authenticate('test-user', 'test-pass');

        self::assertSame('48980806|abc', $token->accessToken);
        self::assertStringContainsString('/login?username=test-user&password=test-pass', (string) $httpClient->lastRequest?->getUri());
    }

    public function testFailEnvelopeIsAnAuthenticationException(): void
    {
        $httpClient = new FakeHttpClient(static fn() => Fixture::fail('Invalid credentials'));
        $factory = new HttpFactory();

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid credentials');

        (new CredentialsAuthenticator($httpClient, $factory, new ClientConfig()))
            ->authenticate('test-user', 'wrong-password');
    }

    public function testHttpErrorIsAnAuthenticationException(): void
    {
        $httpClient = new FakeHttpClient(static fn() => Fixture::raw('nope', 500));
        $factory = new HttpFactory();

        $this->expectException(AuthenticationException::class);

        (new CredentialsAuthenticator($httpClient, $factory, new ClientConfig()))
            ->authenticate('test-user', 'test-pass');
    }
}
