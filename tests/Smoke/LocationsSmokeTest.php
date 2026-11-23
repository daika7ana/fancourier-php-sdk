<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Tests\Smoke;

use Daika7ana\FanCourier\DTOs\Common\PaginatedResult;
use Daika7ana\FanCourier\DTOs\Location\Country;
use Daika7ana\FanCourier\DTOs\Location\County;
use Daika7ana\FanCourier\DTOs\Location\ExternalCounty;
use Daika7ana\FanCourier\DTOs\Location\ExternalLocality;
use Daika7ana\FanCourier\DTOs\Location\Locality;
use Daika7ana\FanCourier\DTOs\Location\Street;
use Daika7ana\SimpleCollections\Collection;

final class LocationsSmokeTest extends SmokeTestCase
{
    public function testCountiesReturnsTypedCollection(): void
    {
        $counties = $this->client()->locations()->counties();

        $this->assertInstanceOf(Collection::class, $counties);
        $this->assertGreaterThan(0, $counties->count());

        foreach ($counties as $county) {
            $this->assertInstanceOf(County::class, $county);
            $this->assertNotSame('', $county->name);
        }
    }

    public function testLocalitiesForFirstCountyReturnTypedRows(): void
    {
        $counties = $this->client()->locations()->counties();
        $county = $counties->first();

        $this->assertInstanceOf(County::class, $county);

        $localities = $this->client()->locations()->localities($county->name);

        $this->assertInstanceOf(Collection::class, $localities);
        $this->assertGreaterThan(0, $localities->count());

        foreach ($localities as $locality) {
            $this->assertInstanceOf(Locality::class, $locality);
            $this->assertNotSame('', $locality->name);
        }
    }

    public function testStreetsForFirstCountyAndLocalityReturnTypedRows(): void
    {
        $locations = $this->client()->locations();
        $county = $locations->counties()->first();

        $this->assertInstanceOf(County::class, $county);

        $locality = $locations->localities($county->name)->first();

        $this->assertInstanceOf(Locality::class, $locality);

        $result = $locations->streets($county->name, $locality->name, page: 1, perPage: 5);

        $this->assertInstanceOf(PaginatedResult::class, $result);
        $this->assertSame(1, $result->currentPage);
        $this->assertNotNull($result->total);
        $this->assertLessThanOrEqual(5, $result->items->count());

        // the API also returns placeholder rows with an empty street name, so only the type is guaranteed
        foreach ($result->items as $street) {
            $this->assertInstanceOf(Street::class, $street);
        }
    }

    public function testCountriesReturnsTypedCollection(): void
    {
        $countries = $this->client()->locations()->countries();

        $this->assertInstanceOf(Collection::class, $countries);
        $this->assertGreaterThan(0, $countries->count());

        foreach ($countries as $country) {
            $this->assertInstanceOf(Country::class, $country);
            $this->assertNotSame('', $country->name);
        }
    }

    public function testExternalCountiesForFirstForeignCountryReturnTypedRows(): void
    {
        $country = $this->firstForeignCountry();

        $counties = $this->client()->locations()->externalCounties($country->name);

        $this->assertInstanceOf(Collection::class, $counties);

        foreach ($counties as $county) {
            $this->assertInstanceOf(ExternalCounty::class, $county);
        }
    }

    public function testExternalLocalitiesForFirstForeignCountryReturnTypedRows(): void
    {
        $country = $this->firstForeignCountry();

        $result = $this->client()->locations()->externalLocalities($country->name, page: 1, perPage: 5);

        $this->assertInstanceOf(PaginatedResult::class, $result);

        foreach ($result->items as $locality) {
            $this->assertInstanceOf(ExternalLocality::class, $locality);
        }
    }

    private function firstForeignCountry(): Country
    {
        foreach ($this->client()->locations()->countries() as $country) {
            if ($country->code !== null && $country->code !== '' && $country->code !== 'RO') {
                return $country;
            }
        }

        $this->markTestSkipped('countries() returned no foreign country to query the external lookups.');

        throw new \RuntimeException('Unreachable.');
    }
}
