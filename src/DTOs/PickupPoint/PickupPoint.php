<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\PickupPoint;

use Daika7ana\FanCourier\Support\JsonHelper;
use Daika7ana\FanCourier\DTOs\Common\Address;

final readonly class PickupPoint
{
    /**
     * @param array<int|string, PickupSchedule> $schedule
     * @param array<int|string, PickupDrawer> $drawer
     * @param list<string> $paymentMethods
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
        public array $paymentMethods = [],
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
            paymentMethods: self::listOfStrings($data['paymentMethods'] ?? null),
        );
    }

    /**
     * @template TItem
     *
     * @param callable(array<string, mixed>): TItem $mapper
     *
     * @return array<int|string, TItem>
     */
    private static function listOf(mixed $value, callable $mapper): array
    {
        $items = [];

        // Keys are kept: the spec's schedule is positional, but a keyed one survives.
        foreach (is_array($value) ? $value : [] as $key => $item) {
            if (is_array($item)) {
                $items[$key] = $mapper($item);
            }
        }

        return $items;
    }

    /**
     * @return list<string>
     */
    private static function listOfStrings(mixed $value): array
    {
        return array_values(array_map(
            static fn(mixed $item): string => (string) $item,
            array_filter(
                is_array($value) ? $value : [],
                static fn(mixed $item): bool => is_scalar($item),
            ),
        ));
    }

    private static function string(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }
}
