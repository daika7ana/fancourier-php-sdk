<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\PickupPoint;

use Daika7ana\FanCourier\Support\JsonHelper;
use Daika7ana\FanCourier\DTOs\Common\Address;

final readonly class PickupPoint
{
    /**
     * @param list<PickupSchedule> $schedule
     * @param list<PickupDrawer> $drawer
     */
    public function __construct(
        public int|string $id,
        public string $name,
        public ?string $routingLocation = null,
        public ?string $description = null,
        public ?Address $address = null,
        public ?string $latitude = null,
        public ?string $longitude = null,
        public array $schedule = [],
        public array $drawer = [],
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $address = $data['address'] ?? null;

        return new self(
            id: JsonHelper::id($data['id'] ?? null) ?? '',
            name: is_scalar($data['name'] ?? null) ? (string) $data['name'] : '',
            routingLocation: self::string($data['routingLocation'] ?? null),
            description: self::string($data['description'] ?? null),
            address: is_array($address) ? Address::fromArray($address) : null,
            latitude: self::string($data['latitude'] ?? null),
            longitude: self::string($data['longitude'] ?? null),
            schedule: self::listOf($data['schedule'] ?? null, PickupSchedule::fromArray(...)),
            drawer: self::listOf($data['drawer'] ?? null, PickupDrawer::fromArray(...)),
        );
    }

    /**
     * @template TItem
     *
     * @param callable(array<string, mixed>): TItem $mapper
     *
     * @return list<TItem>
     */
    private static function listOf(mixed $value, callable $mapper): array
    {
        $items = [];

        foreach (is_array($value) ? $value : [] as $item) {
            if (is_array($item)) {
                $items[] = $mapper($item);
            }
        }

        return $items;
    }

    private static function string(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }
}
