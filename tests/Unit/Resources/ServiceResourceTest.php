<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Tests\Unit\Resources;

use Daika7ana\FanCourier\Enums\ServiceType;
use Daika7ana\FanCourier\Tests\Support\Fixture;
use Daika7ana\FanCourier\Tests\Support\TestClientFactory;
use PHPUnit\Framework\TestCase;

final class ServiceResourceTest extends TestCase
{
    public function testAllParsesServices(): void
    {
        [$client] = TestClientFactory::create(static fn() => Fixture::ok([
            ['id' => 1, 'name' => 'Standard', 'description' => 'livrare 1-3 zile'],
            ['id' => 2, 'name' => 'RedCode', 'description' => null],
        ]));

        $services = $client->services()->all();

        self::assertCount(2, $services);
        self::assertSame(1, $services[0]->id);
        self::assertSame('Standard', $services[0]->name);
        self::assertSame('livrare 1-3 zile', $services[0]->description);
        self::assertSame('RedCode', $services[1]->name);
        self::assertNull($services[1]->description);
    }

    public function testOptionsSendsClientIdAndService(): void
    {
        [$client, $http] = TestClientFactory::create(static fn() => Fixture::ok([
            ['id' => 'V', 'name' => 'PickUp'],
            ['id' => 'X', 'name' => 'ePOD'],
        ]));

        $options = $client->services()->options(ServiceType::Fanbox);

        self::assertCount(2, $options);
        self::assertSame('V', $options[0]->id);
        self::assertSame('PickUp', $options[0]->name);

        $request = $http->lastRequest;
        self::assertNotNull($request);
        self::assertSame('/reports/service-options', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);

        self::assertSame('7032158', $query['clientId']);
        self::assertSame('FANbox', $query['service']);
    }
}
