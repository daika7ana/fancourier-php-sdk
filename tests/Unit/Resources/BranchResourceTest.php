<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Tests\Unit\Resources;

use Daika7ana\FanCourier\DTOs\Branch\Branch;
use Daika7ana\FanCourier\Tests\Support\Fixture;
use Daika7ana\FanCourier\Tests\Support\TestClientFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class BranchResourceTest extends TestCase
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

    public function testAllPassesFiltersAndMapsLowercaseZipcode(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::ok([
                [
                    'id' => 7032158,
                    'name' => 'FAN COURIER - cont test',
                    'bank' => 'RAIFFEISEN BANK ROMANA',
                    'bankAccount' => 'RO53RZBR0000060009520959',
                    'email' => 'it@fancourier.ro',
                    'phone' => '0722333111',
                    'secondaryPhone' => '0799999888',
                    'contactPerson' => 'Jon Snow',
                    'address' => [
                        'locality' => 'Bucuresti',
                        'county' => 'Bucuresti',
                        'street' => 'Fabrica de Glucoza (sosea)',
                        'streetNo' => '11C',
                        'zipcode' => '020331',
                        'building' => 'FAN',
                        'entrance' => '',
                        'floor' => '',
                        'apartment' => '',
                    ],
                ],
            ]),
        );

        $branches = $client->branches()->all('Bucuresti', 'Bucuresti');

        self::assertSame('/reports/branches', $http->lastRequest?->getUri()->getPath());
        self::assertSame(['county' => 'Bucuresti', 'locality' => 'Bucuresti'], self::query($http->lastRequest));
        self::assertCount(1, $branches);
        $branch = $branches->first();
        self::assertInstanceOf(Branch::class, $branch);
        self::assertSame(7032158, $branch->id);
        self::assertSame('RAIFFEISEN BANK ROMANA', $branch->bank);
        self::assertSame('Jon Snow', $branch->contactPerson);
        self::assertSame('020331', $branch->address?->zipCode);
        self::assertSame('11C', $branch->address?->streetNo);
    }

    public function testAllWithoutFiltersOmitsQuery(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::ok([]),
        );

        $branches = $client->branches()->all();

        self::assertCount(0, $branches);
        self::assertSame([], self::query($http->lastRequest));
    }
}
