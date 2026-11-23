<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Resources;

use Daika7ana\FanCourier\Client;
use Daika7ana\FanCourier\DTOs\Branch\Branch;
use Daika7ana\SimpleCollections\Collection;
use Daika7ana\FanCourier\Exceptions\TransportException;
use Daika7ana\FanCourier\Exceptions\UnexpectedStatusException;
use Daika7ana\FanCourier\Exceptions\ValidationException;
use Daika7ana\FanCourier\Support\ApiResponseMapper;

final class BranchResource
{
    public function __construct(private Client $client) {}

    /**
     * @return Collection<int, Branch>
     *
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function all(?string $county = null, ?string $locality = null): Collection
    {
        $payload = ApiResponseMapper::decodeJson(
            $this->client->send($this->client->createRequest('GET', '/reports/branches', [
                'county' => $county,
                'locality' => $locality,
            ])),
        );

        return new Collection(array_map(
            static fn(array $row): Branch => Branch::fromArray($row),
            ApiResponseMapper::listOfArrays($payload['data'] ?? null),
        ));
    }
}
