<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Tests\Unit\Resources;

use Daika7ana\FanCourier\DTOs\Common\Collection;
use Daika7ana\FanCourier\DTOs\Location\Country;
use Daika7ana\FanCourier\DTOs\Location\County;
use Daika7ana\FanCourier\DTOs\Location\ExternalCounty;
use Daika7ana\FanCourier\DTOs\Location\ExternalLocality;
use Daika7ana\FanCourier\DTOs\Location\Locality;
use Daika7ana\FanCourier\DTOs\Location\Street;
use Daika7ana\FanCourier\Tests\Support\Fixture;
use Daika7ana\FanCourier\Tests\Support\TestClientFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

final class LocationResourceTest extends TestCase
{
    /**
     * @return array<string, string>
     */
    private static function query(RequestInterface $request): array
    {
        parse_str($request->getUri()->getQuery(), $query);

        /** @var array<string, string> $query */
        return $query;
    }

    public function testCountiesRequestsReportPathAndMapsItems(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): \Psr\Http\Message\ResponseInterface => Fixture::ok([
                ['id' => 11, 'name' => 'Bucuresti', 'extra' => 'ignored'],
            ]),
        );

        $counties = $client->locations()->counties();

        self::assertSame('/reports/counties', $http->lastRequest?->getUri()->getPath());
        self::assertSame([], self::query($http->lastRequest));
        self::assertInstanceOf(Collection::class, $counties);
        self::assertCount(1, $counties);
        self::assertInstanceOf(County::class, $counties->first());
        self::assertSame(11, $counties->first()->id);
        self::assertSame('Bucuresti', $counties->first()->name);
    }

    public function testLocalitiesPassesCountyFilter(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): \Psr\Http\Message\ResponseInterface => Fixture::ok([
                ['id' => 11, 'name' => 'Bucuresti', 'county' => 'Bucuresti', 'agency' => 'Bucuresti', 'exteriorKm' => 0],
            ]),
        );

        $localities = $client->locations()->localities('Bucuresti');

        self::assertSame('/reports/localities', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['county' => 'Bucuresti'], self::query($http->lastRequest));
        self::assertInstanceOf(Locality::class, $localities->first());
        self::assertSame(0, $localities->first()->exteriorKm);
        self::assertSame('Bucuresti', $localities->first()->agency);
    }

    public function testLocalitiesWithoutFilterOmitsCounty(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): \Psr\Http\Message\ResponseInterface => Fixture::ok([]),
        );

        $client->locations()->localities();

        self::assertSame([], self::query($http->lastRequest));
    }

    public function testStreetsPassesFiltersAndMapsPaginationCounters(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): \Psr\Http\Message\ResponseInterface => Fixture::json([
                'status' => 'success',
                'total' => 5313,
                'perPage' => 1,
                'currentPage' => 1,
                'data' => [
                    [
                        'id' => 105702,
                        'street' => '1 Decembrie 1918',
                        'type' => 'Bulevard',
                        'locality' => 'Bucuresti',
                        'county' => 'Bucuresti',
                        'details' => [
                            'fromNo' => '36',
                            'toNo' => '40',
                            'parityNo' => '0',
                            'zipCode' => '032466',
                            'visualCode' => '-',
                            'routingCode' => '0353',
                        ],
                    ],
                ],
            ]),
        );

        $result = $client->locations()->streets('Bucuresti', 'Bucuresti', 1, 1);

        self::assertSame('/reports/streets', $http->lastRequest?->getUri()->getPath());
        self::assertSame(
            ['county' => 'Bucuresti', 'locality' => 'Bucuresti', 'page' => '1', 'perPage' => '1'],
            self::query($http->lastRequest),
        );
        self::assertSame(5313, $result->total);
        self::assertSame(1, $result->perPage);
        self::assertSame(1, $result->currentPage);
        $street = $result->items->first();
        self::assertInstanceOf(Street::class, $street);
        self::assertSame('1 Decembrie 1918', $street->street);
        self::assertSame('Bulevard', $street->type);
        self::assertSame('032466', $street->details?->zipCode);
        self::assertSame('0353', $street->details?->routingCode);
    }

    public function testCountriesReadsCodeIsoOrIsoCodeInOrder(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): \Psr\Http\Message\ResponseInterface => Fixture::ok([
                ['id' => 1, 'name' => 'Romania', 'iso' => 'RO', 'isoCode' => 'ROU'],
                ['id' => 2, 'name' => 'Moldova', 'code' => 'MD'],
            ]),
        );

        $countries = $client->locations()->countries();

        self::assertSame('/reports/countries', $http->lastRequest?->getUri()->getPath());
        self::assertCount(2, $countries);
        self::assertInstanceOf(Country::class, $countries->first());
        self::assertSame('RO', $countries[0]->code);
        self::assertSame('MD', $countries[1]->code);
    }

    public function testExternalCountiesPassesCountryFilter(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): \Psr\Http\Message\ResponseInterface => Fixture::ok([
                ['id' => 10, 'name' => 'Basarabeasca', 'country' => 'Moldova'],
            ]),
        );

        $counties = $client->locations()->externalCounties('Moldova');

        self::assertSame('/reports/external-counties', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['country' => 'Moldova'], self::query($http->lastRequest));
        self::assertInstanceOf(ExternalCounty::class, $counties->first());
        self::assertSame('Moldova', $counties->first()->country);
    }

    public function testExternalLocalitiesAcceptsStringPerPageAndKeepsFilters(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): \Psr\Http\Message\ResponseInterface => Fixture::json([
                'status' => 'success',
                'perPage' => '1',
                'currentPage' => 1,
                'total' => 10,
                'data' => [
                    ['id' => 185960, 'name' => 'Abaclia', 'county' => 'Basarabeasca', 'country' => 'Moldova'],
                ],
            ]),
        );

        $result = $client->locations()->externalLocalities('Moldova', 'Basarabeasca', 1, 1);

        self::assertSame('/reports/external-localities', $http->lastRequest?->getUri()->getPath());
        self::assertSame(
            ['country' => 'Moldova', 'county' => 'Basarabeasca', 'perPage' => '1', 'page' => '1'],
            self::query($http->lastRequest),
        );
        self::assertSame(1, $result->perPage);
        self::assertSame(1, $result->currentPage);
        self::assertSame(10, $result->total);
        self::assertInstanceOf(ExternalLocality::class, $result->items->first());
        self::assertSame('Abaclia', $result->items->first()->name);
    }
}
