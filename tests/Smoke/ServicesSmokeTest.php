<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Tests\Smoke;

use Daika7ana\FanCourier\DTOs\Service\Service;
use Daika7ana\FanCourier\DTOs\Service\ServiceOption;
use Daika7ana\SimpleCollections\Collection;

final class ServicesSmokeTest extends SmokeTestCase
{
    public function testServicesReturnsTypedCatalogue(): void
    {
        $services = $this->client()->services()->all();

        $this->assertInstanceOf(Collection::class, $services);
        $this->assertGreaterThan(0, $services->count());

        foreach ($services as $service) {
            $this->assertInstanceOf(Service::class, $service);
            $this->assertNotSame('', $service->name);
        }
    }

    public function testServiceOptionsForFirstServiceReturnTypedOptions(): void
    {
        $services = $this->client()->services()->all();
        $first = $services->first();

        $this->assertInstanceOf(Service::class, $first);

        $options = $this->client()->services()->options($first->name);

        $this->assertInstanceOf(Collection::class, $options);

        foreach ($options as $option) {
            $this->assertInstanceOf(ServiceOption::class, $option);
            $this->assertNotSame('', $option->id);
            $this->assertNotSame('', $option->name);
        }
    }
}
