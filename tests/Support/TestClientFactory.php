<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Tests\Support;

use Daika7ana\FanCourier\Client;
use Daika7ana\FanCourier\Config\ClientConfig;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class TestClientFactory
{
    public static function clientId(): string
    {
        return '7032158';
    }

    /**
     * @param callable(RequestInterface): ResponseInterface $handler
     * @return array{Client, FakeHttpClient}
     */
    public static function create(callable $handler, ?ClientConfig $config = null): array
    {
        $httpClient = new FakeHttpClient($handler);
        $factory = new HttpFactory();

        $client = Client::create(
            httpClient: $httpClient,
            requestFactory: $factory,
            streamFactory: $factory,
            config: $config ?? new ClientConfig(clientId: self::clientId()),
        );

        return [$client, $httpClient];
    }
}
