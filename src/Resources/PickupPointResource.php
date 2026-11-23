<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Resources;

use Daika7ana\FanCourier\Client;
use Daika7ana\SimpleCollections\Collection;
use Daika7ana\FanCourier\DTOs\PickupPoint\PickupPoint;
use Daika7ana\FanCourier\Enums\PickupPointType;
use Daika7ana\FanCourier\Exceptions\TransportException;
use Daika7ana\FanCourier\Exceptions\UnexpectedStatusException;
use Daika7ana\FanCourier\Exceptions\ValidationException;
use Daika7ana\FanCourier\Support\ApiResponseMapper;

final class PickupPointResource
{
    public function __construct(private Client $client) {}

    /**
     * @return Collection<int, PickupPoint>
     *
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function all(PickupPointType|string $type): Collection
    {
        $payload = ApiResponseMapper::decodeJson(
            $this->client->send($this->client->createRequest('GET', '/reports/pickup-points', [
                'type' => $type instanceof PickupPointType ? $type->value : $type,
            ])),
        );

        return $this->mapItems($payload);
    }

    /**
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function get(string $id): ?PickupPoint
    {
        $payload = ApiResponseMapper::decodeJson(
            $this->client->send($this->client->createRequest('GET', '/reports/pickup-points', [
                'id' => $id,
            ])),
        );

        return $this->mapItems($payload)
            ->filter(static fn(PickupPoint $point): bool => (string) $point->id === $id)
            ->first();
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return Collection<int, PickupPoint>
     */
    private function mapItems(array $payload): Collection
    {
        return new Collection(array_map(
            static fn(array $row): PickupPoint => PickupPoint::fromArray($row),
            ApiResponseMapper::listOfArrays($payload['data'] ?? null),
        ));
    }
}
