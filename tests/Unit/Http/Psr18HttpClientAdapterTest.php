<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Tests\Unit\Http;

use Daika7ana\FanCourier\Client;
use Daika7ana\FanCourier\Config\ClientConfig;
use Daika7ana\FanCourier\Exceptions\TransportException;
use Daika7ana\FanCourier\Http\Psr18HttpClientAdapter;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class Psr18HttpClientAdapterTest extends TestCase
{
    public function testDelegatesToTheWrappedClient(): void
    {
        $inner = new class implements ClientInterface {
            public ?RequestInterface $lastRequest = null;

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->lastRequest = $request;

                return new Response(200, [], 'ok');
            }
        };

        $request = new Request('GET', 'https://example.test/ping');

        $response = (new Psr18HttpClientAdapter($inner))->sendRequest($request);

        $this->assertSame($request, $inner->lastRequest);
        $this->assertSame('ok', (string) $response->getBody());
    }

    public function testNonNullTimeoutIsRejectedWithoutCallingTheWrappedClient(): void
    {
        $inner = new class implements ClientInterface {
            public int $calls = 0;

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                ++$this->calls;

                return new Response(200);
            }
        };

        $adapter = new Psr18HttpClientAdapter($inner);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A foreign PSR-18 client cannot honour the SDK per-call timeout; configure the timeout on that client.');

        try {
            $adapter->sendRequest(new Request('GET', 'https://example.test/ping'), 30.0);
        } finally {
            $this->assertSame(0, $inner->calls);
        }
    }

    public function testClientExceptionSurfacesAsTransportException(): void
    {
        $request = new Request('GET', 'https://example.test/ping');
        $original = new ConnectException('Connection refused', $request);

        $inner = new class ($original) implements ClientInterface {
            public function __construct(private ConnectException $exception) {}

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                throw $this->exception;
            }
        };

        try {
            (new Psr18HttpClientAdapter($inner))->sendRequest($request);
            $this->fail('Expected a TransportException.');
        } catch (TransportException $e) {
            $this->assertSame($original, $e->getPrevious());
            $this->assertSame('Connection refused', $e->getMessage());
        }
    }

    public function testClientAcceptsBarePsr18Client(): void
    {
        $bare = new class implements ClientInterface {
            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                return new Response(200);
            }
        };

        $factory = new HttpFactory();

        $client = Client::create(
            httpClient: $bare,
            requestFactory: $factory,
            streamFactory: $factory,
            config: new ClientConfig(clientId: '7032158'),
        );

        $this->assertInstanceOf(Client::class, $client);
    }
}
