<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Resources;

use Daika7ana\FanCourier\Client;
use Daika7ana\SimpleCollections\Collection;
use Daika7ana\FanCourier\DTOs\Common\PaginatedResult;
use Daika7ana\FanCourier\DTOs\Location\Country;
use Daika7ana\FanCourier\DTOs\Location\County;
use Daika7ana\FanCourier\DTOs\Location\ExternalCounty;
use Daika7ana\FanCourier\DTOs\Location\ExternalLocality;
use Daika7ana\FanCourier\DTOs\Location\Locality;
use Daika7ana\FanCourier\DTOs\Location\Street;
use Daika7ana\FanCourier\Exceptions\ApiException;
use Daika7ana\FanCourier\Exceptions\TransportException;
use Daika7ana\FanCourier\Exceptions\UnexpectedStatusException;
use Daika7ana\FanCourier\Exceptions\ValidationException;
use Daika7ana\FanCourier\Support\ApiResponseMapper;

final class LocationResource
{
    public function __construct(private Client $client) {}

    /**
     * @return Collection<int, County>
     *
     * @throws ApiException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function counties(): Collection
    {
        $payload = ApiResponseMapper::decodeJson(
            $this->client->send($this->client->createRequest('GET', '/reports/counties')),
        );

        return new Collection(array_map(
            static fn(array $row): County => County::fromArray($row),
            ApiResponseMapper::listOfArrays($payload['data'] ?? null),
        ));
    }

    /**
     * @return Collection<int, Locality>
     *
     * @throws ApiException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function localities(?string $county = null): Collection
    {
        $payload = ApiResponseMapper::decodeJson(
            $this->client->send($this->client->createRequest('GET', '/reports/localities', [
                'county' => $county,
            ])),
        );

        return new Collection(array_map(
            static fn(array $row): Locality => Locality::fromArray($row),
            ApiResponseMapper::listOfArrays($payload['data'] ?? null),
        ));
    }

    /**
     * @return PaginatedResult<Street>
     *
     * @throws ApiException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function streets(
        ?string $county = null,
        ?string $locality = null,
        ?int $page = null,
        ?int $perPage = null,
    ): PaginatedResult {
        $payload = ApiResponseMapper::decodeJson(
            $this->client->send($this->client->createRequest('GET', '/reports/streets', [
                'county' => $county,
                'locality' => $locality,
                'page' => $page,
                'perPage' => $perPage,
            ])),
        );

        return PaginatedResult::fromArray($payload, static fn(array $row): Street => Street::fromArray($row));
    }

    /**
     * @return Collection<int, Country>
     *
     * @throws ApiException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function countries(): Collection
    {
        $payload = ApiResponseMapper::decodeJson(
            $this->client->send($this->client->createRequest('GET', '/reports/countries')),
        );

        return new Collection(array_map(
            static fn(array $row): Country => Country::fromArray($row),
            ApiResponseMapper::listOfArrays($payload['data'] ?? null),
        ));
    }

    /**
     * @return Collection<int, ExternalCounty>
     *
     * @throws ApiException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function externalCounties(?string $country = null): Collection
    {
        $payload = ApiResponseMapper::decodeJson(
            $this->client->send($this->client->createRequest('GET', '/reports/external-counties', [
                'country' => $country,
            ])),
        );

        return new Collection(array_map(
            static fn(array $row): ExternalCounty => ExternalCounty::fromArray($row),
            ApiResponseMapper::listOfArrays($payload['data'] ?? null),
        ));
    }

    /**
     * @return PaginatedResult<ExternalLocality>
     *
     * @throws ApiException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function externalLocalities(
        ?string $country = null,
        ?string $county = null,
        ?int $page = null,
        ?int $perPage = null,
    ): PaginatedResult {
        $payload = ApiResponseMapper::decodeJson(
            $this->client->send($this->client->createRequest('GET', '/reports/external-localities', [
                'country' => $country,
                'county' => $county,
                'perPage' => $perPage,
                'page' => $page,
            ])),
        );

        return PaginatedResult::fromArray($payload, static fn(array $row): ExternalLocality => ExternalLocality::fromArray($row));
    }
}
