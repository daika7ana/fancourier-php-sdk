<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Order;

use Daika7ana\FanCourier\DTOs\Common\Dimensions;
use Daika7ana\FanCourier\DTOs\Common\Packages;
use Daika7ana\FanCourier\Enums\OrderType;

final readonly class OrderInfo
{
    public function __construct(
        public Packages $packages,
        public float $weight,
        public Dimensions $dimensions,
        public OrderType|string $orderType,
        public ?string $pickupDate = null,
        public ?PickupHours $pickupHours = null,
        public ?string $observations = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            packages: Packages::fromArray(self::nested($data['packages'] ?? null)),
            weight: is_numeric($data['weight'] ?? null) ? (float) $data['weight'] : 0.0,
            dimensions: Dimensions::fromArray(self::nested($data['dimensions'] ?? null)),
            orderType: self::orderType($data['orderType'] ?? null),
            pickupDate: self::string($data['pickupDate'] ?? null),
            pickupHours: is_array($data['pickupHours'] ?? null) ? PickupHours::fromArray($data['pickupHours']) : null,
            observations: self::string($data['observations'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'packages' => $this->packages->toArray(),
            'weight' => $this->weight,
            'dimensions' => $this->dimensions->toArray(),
            'orderType' => $this->orderType instanceof OrderType ? $this->orderType->value : $this->orderType,
            'pickupDate' => $this->pickupDate,
            'pickupHours' => $this->pickupHours?->toArray(),
            'observations' => $this->observations,
        ];

        return array_filter($data, static fn(mixed $value): bool => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    private static function nested(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    private static function string(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }

    private static function orderType(mixed $value): OrderType|string
    {
        return $value instanceof OrderType ? $value : (is_scalar($value) ? (string) $value : '');
    }
}
