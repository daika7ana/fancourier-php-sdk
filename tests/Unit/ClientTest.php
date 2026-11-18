<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Tests\Unit;

use Daika7ana\FanCourier\Auth\Token;
use Daika7ana\FanCourier\Client;
use Daika7ana\FanCourier\Config\ClientConfig;
use Daika7ana\FanCourier\Exceptions\ValidationException;
use Daika7ana\FanCourier\Tests\Support\FakeHttpClient;
use Daika7ana\FanCourier\Tests\Support\Fixture;
use Daika7ana\FanCourier\Tests\Support\TestClientFactory;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

final class ClientTest extends TestCase
{
    public function testCreateRequestBuildsQueryAndAuthorizationHeader(): void
    {
        $factory = new HttpFactory();
        $client = Client::create(
            httpClient: new FakeHttpClient(static fn(): \Psr\Http\Message\ResponseInterface => Fixture::ok([])),
            requestFactory: $factory,
            streamFactory: $factory,
            config: new ClientConfig(clientId: 7032158),
        );
        $client->setToken(new Token('abc'));

        $request = $client->createRequest('GET', '/reports/streets', [
            'county' => 'Bucuresti',
            'awbs' => ['1', '2'],
            'pdf' => false,
            'page' => 1,
        ]);

        self::assertSame(
            'https://api.fancourier.ro/reports/streets?county=Bucuresti&awbs%5B%5D=1&awbs%5B%5D=2&pdf=0&page=1',
            (string) $request->getUri(),
        );
        self::assertSame('Bearer abc', $request->getHeaderLine('Authorization'));
        self::assertSame('application/json', $request->getHeaderLine('Accept'));
    }

    public function testNullQueryValuesAreOmitted(): void
    {
        $factory = new HttpFactory();
        $client = Client::create(
            httpClient: new FakeHttpClient(static fn(): \Psr\Http\Message\ResponseInterface => Fixture::ok([])),
            requestFactory: $factory,
            streamFactory: $factory,
            config: new ClientConfig(clientId: '1'),
        );

        $request = $client->createRequest('GET', '/reports/localities', ['county' => null]);

        self::assertSame('https://api.fancourier.ro/reports/localities', (string) $request->getUri());
        self::assertFalse($request->hasHeader('Authorization'));
    }

    public function testAuthenticateStoresTokenAndCredentials(): void
    {
        $calls = [];
        [$client] = TestClientFactory::create(function (RequestInterface $request) use (&$calls) {
            $calls[] = (string) $request->getUri();

            return Fixture::ok(['token' => 'fresh-token', 'expiresAt' => '+1 day']);
        });

        $client->authenticate('test-user', 'test-pass');

        self::assertCount(1, $calls);
        self::assertStringContainsString('/login?username=test-user&password=test-pass', $calls[0]);
        self::assertSame('fresh-token', $client->getToken()?->accessToken);
    }

    public function testExpiredTokenIsRefreshedBeforeSending(): void
    {
        $calls = [];
        $config = new ClientConfig(
            clientId: '7032158',
            username: 'test-user',
            password: 'test-pass',
        );

        [$client, $httpClient] = TestClientFactory::create(function (RequestInterface $request) use (&$calls) {
            $calls[] = (string) $request->getUri();

            return str_contains((string) $request->getUri(), '/login')
                ? Fixture::ok(['token' => 'fresh', 'expiresAt' => '+1 day'])
                : Fixture::ok([]);
        }, $config);

        $client->setToken(new Token('stale', 'Bearer', new \DateTimeImmutable('-1 hour')));
        $client->send($client->createRequest('GET', '/reports/services'));

        self::assertCount(2, $calls);
        self::assertStringContainsString('/login', $calls[0]);
        self::assertSame('Bearer fresh', $httpClient->lastRequest?->getHeaderLine('Authorization'));
    }

    public function testRejectedTokenIsRetriedOnceAfterReauthentication(): void
    {
        $calls = [];
        $config = new ClientConfig(
            clientId: '7032158',
            username: 'test-user',
            password: 'test-pass',
        );

        [$client] = TestClientFactory::create(function (RequestInterface $request) use (&$calls) {
            $calls[] = (string) $request->getUri();

            if (str_contains((string) $request->getUri(), '/login')) {
                return Fixture::ok(['token' => 'fresh', 'expiresAt' => '+1 day']);
            }

            return $request->getHeaderLine('Authorization') === 'Bearer fresh'
                ? Fixture::ok([])
                : Fixture::raw('{"status":"fail","message":"Unauthenticated."}', 401);
        }, $config);

        $client->setToken(new Token('revoked'));

        $response = $client->send($client->createRequest('GET', '/reports/services'));

        self::assertSame(200, $response->getStatusCode());
        self::assertCount(3, $calls);
    }

    public function testConfiguredCredentialsLogInAfterA401(): void
    {
        $calls = [];
        $config = new ClientConfig(clientId: '7032158', username: 'test-user', password: 'test-pass');

        [$client, $httpClient] = TestClientFactory::create(function (RequestInterface $request) use (&$calls) {
            $calls[] = (string) $request->getUri();

            if (str_contains((string) $request->getUri(), '/login')) {
                return Fixture::ok(['token' => 'fresh', 'expiresAt' => '+1 day']);
            }

            return $request->getHeaderLine('Authorization') === 'Bearer fresh'
                ? Fixture::ok([])
                : Fixture::raw('{"status":"fail","message":"Unauthenticated."}', 401);
        }, $config);

        $response = $client->send($client->createRequest('GET', '/reports/services'));

        self::assertSame(200, $response->getStatusCode());
        self::assertCount(3, $calls);
        self::assertStringContainsString('/login?username=test-user&password=test-pass', $calls[1]);
        self::assertSame('Bearer fresh', $httpClient->lastRequest?->getHeaderLine('Authorization'));
    }

    public function testPublicEndpointIsSentWithoutLoggingIn(): void
    {
        $calls = [];
        $config = new ClientConfig(clientId: '7032158', username: 'wrong', password: 'wrong');

        [$client] = TestClientFactory::create(function (RequestInterface $request) use (&$calls) {
            $calls[] = (string) $request->getUri();

            return Fixture::ok([['id' => 1, 'name' => 'Constanta']]);
        }, $config);

        $client->locations()->counties();

        self::assertCount(1, $calls);
        self::assertStringNotContainsString('/login', $calls[0]);
    }

    public function testBusiness403IsNotRetried(): void
    {
        $calls = [];
        $config = new ClientConfig(clientId: '7032158', username: 'test-user', password: 'test-pass');

        [$client] = TestClientFactory::create(function (RequestInterface $request) use (&$calls) {
            $calls[] = (string) $request->getUri();

            return Fixture::json(['status' => 'fail', 'message' => 'This order was already deleted'], 403);
        }, $config);

        $client->setToken(new Token('valid'));

        $response = $client->send($client->createRequest('DELETE', '/order'));

        self::assertSame(403, $response->getStatusCode());
        self::assertCount(1, $calls);
    }

    public function testRejectedTokenIsRetriedOnlyOnce(): void
    {
        $calls = [];
        $config = new ClientConfig(clientId: '7032158', username: 'test-user', password: 'test-pass');

        [$client] = TestClientFactory::create(function (RequestInterface $request) use (&$calls) {
            $calls[] = (string) $request->getUri();

            return str_contains((string) $request->getUri(), '/login')
                ? Fixture::ok(['token' => 'fresh', 'expiresAt' => '+1 day'])
                : Fixture::raw('{"status":"fail","message":"Unauthenticated."}', 401);
        }, $config);

        $client->setToken(new Token('revoked'));

        $response = $client->send($client->createRequest('GET', '/reports/services'));

        self::assertSame(401, $response->getStatusCode());
        self::assertCount(3, $calls);
    }

    public function testMissingClientIdIsRejected(): void
    {
        $factory = new HttpFactory();
        $client = Client::create(
            httpClient: new FakeHttpClient(static fn(): \Psr\Http\Message\ResponseInterface => Fixture::ok([])),
            requestFactory: $factory,
            streamFactory: $factory,
            config: new ClientConfig(),
        );

        $this->expectException(ValidationException::class);

        $client->clientId();
    }

    public function testSetTokenAcceptsString(): void
    {
        $factory = new HttpFactory();
        $client = Client::create(
            httpClient: new FakeHttpClient(static fn(): \Psr\Http\Message\ResponseInterface => Fixture::ok([])),
            requestFactory: $factory,
            streamFactory: $factory,
            config: new ClientConfig(clientId: '1'),
        );

        $client->setToken('raw-token');

        self::assertSame('raw-token', $client->getToken()?->accessToken);
    }
}
