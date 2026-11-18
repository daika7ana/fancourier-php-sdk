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
}
