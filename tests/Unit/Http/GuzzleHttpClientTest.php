<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Tests\Unit\Http;

use Daika7ana\FanCourier\Exceptions\TransportException;
use Daika7ana\FanCourier\Http\GuzzleHttpClient;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

final class GuzzleHttpClientTest extends TestCase
{
    public function testPassesTheTimeoutAndReturnsTheResponse(): void
    {
        $seenTimeout = null;

        $guzzle = new GuzzleClient([
            'handler' => static function (RequestInterface $request, array $options) use (&$seenTimeout) {
                $seenTimeout = $options['timeout'] ?? null;

                return Create::promiseFor(new Response(200, ['Content-Type' => 'application/pdf'], 'pdf-bytes'));
            },
        ]);

        $response = (new GuzzleHttpClient($guzzle))->sendRequest(
            new Request('GET', 'https://api.fancourier.ro/awb/label'),
            5.0,
        );

        self::assertSame(5.0, $seenTimeout);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('pdf-bytes', (string) $response->getBody());
    }

    public function testNonSuccessfulResponseIsReturnedWithoutTimeout(): void
    {
        $mock = new MockHandler([
            new Response(422, ['Content-Type' => 'application/json'], '{"message":"Invalid input"}'),
        ]);

        $adapter = new GuzzleHttpClient(new GuzzleClient(['handler' => HandlerStack::create($mock)]));

        $response = $adapter->sendRequest(new Request('POST', 'https://api.fancourier.ro/awb'));

        self::assertSame(422, $response->getStatusCode());
    }

    public function testWrapsTransportErrors(): void
    {
        $mock = new MockHandler([
            new ConnectException('Connection refused', new Request('GET', 'https://api.fancourier.ro')),
        ]);

        $adapter = new GuzzleHttpClient(new GuzzleClient(['handler' => HandlerStack::create($mock)]));

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Connection refused');

        $adapter->sendRequest(new Request('GET', 'https://api.fancourier.ro/reports/counties'));
    }

    public function testDefaultConstructorConfiguresTransportDefaults(): void
    {
        $adapter = new GuzzleHttpClient();
        $property = new \ReflectionProperty(GuzzleHttpClient::class, 'guzzleClient');
        $guzzle = $property->getValue($adapter);

        self::assertSame(30.0, $guzzle->getConfig('timeout'));
        self::assertSame(10.0, $guzzle->getConfig('connect_timeout'));
    }

    public function testConstructorArgsApplyToAnInjectedClient(): void
    {
        $seen = [];

        $guzzle = new GuzzleClient([
            'handler' => static function (RequestInterface $request, array $options) use (&$seen) {
                $seen = $options;

                return Create::promiseFor(new Response(200));
            },
        ]);

        (new GuzzleHttpClient($guzzle, timeout: 7.0, connectTimeout: 3.0))
            ->sendRequest(new Request('GET', 'https://api.fancourier.ro/reports/counties'));

        self::assertSame(7.0, $seen['timeout']);
        self::assertSame(3.0, $seen['connect_timeout']);
    }

    public function testPerCallTimeoutOverridesConstructorTimeout(): void
    {
        $seen = [];

        $guzzle = new GuzzleClient([
            'handler' => static function (RequestInterface $request, array $options) use (&$seen) {
                $seen = $options;

                return Create::promiseFor(new Response(200));
            },
        ]);

        (new GuzzleHttpClient($guzzle, timeout: 7.0, connectTimeout: 3.0))
            ->sendRequest(new Request('GET', 'https://api.fancourier.ro/reports/counties'), 2.0);

        self::assertSame(2.0, $seen['timeout']);
        self::assertSame(3.0, $seen['connect_timeout']);
    }
}
