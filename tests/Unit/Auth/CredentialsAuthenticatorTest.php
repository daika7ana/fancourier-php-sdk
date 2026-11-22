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
        $response = Fixture::fail('Invalid credentials');
        $httpClient = new FakeHttpClient(static fn() => $response);
        $factory = new HttpFactory();

        try {
            (new CredentialsAuthenticator($httpClient, $factory, new ClientConfig()))
                ->authenticate('test-user', 'wrong-password');
            self::fail('Expected AuthenticationException.');
        } catch (AuthenticationException $e) {
            self::assertStringContainsString('Invalid credentials', $e->getMessage());
            self::assertSame($response, $e->response);
        }
    }

    public function testHttpErrorIsAnAuthenticationException(): void
    {
        $httpClient = new FakeHttpClient(static fn() => Fixture::raw('nope', 500));
        $factory = new HttpFactory();

        $this->expectException(AuthenticationException::class);

        (new CredentialsAuthenticator($httpClient, $factory, new ClientConfig()))
            ->authenticate('test-user', 'test-pass');
    }

    public function testMissingTokenExposesResponse(): void
    {
        $response = Fixture::ok(['expiresAt' => '2030-06-14 06:00:55']);
        $httpClient = new FakeHttpClient(static fn() => $response);
        $factory = new HttpFactory();

        try {
            (new CredentialsAuthenticator($httpClient, $factory, new ClientConfig()))
                ->authenticate('test-user', 'test-pass');
            self::fail('Expected AuthenticationException.');
        } catch (AuthenticationException $e) {
            self::assertStringContainsString('token is missing', $e->getMessage());
            self::assertSame($response, $e->response);
        }
    }
}
