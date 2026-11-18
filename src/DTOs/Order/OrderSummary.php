<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Order;

use Daika7ana\FanCourier\Support\JsonHelper;
use Daika7ana\FanCourier\DTOs\Common\Contact;
use Daika7ana\FanCourier\DTOs\Common\Dimensions;
use Daika7ana\FanCourier\DTOs\Common\Packages;

final readonly class OrderSummary
{
    public function __construct(
        public int|string $id,
        public ?string $number,
        public ?string $date,
        public ?string $hour,
        public Packages $packages,
        public float $weight,
        public Dimensions $dimensions,
        public ?string $pickupDate,
        public ?PickupHours $pickupHours,
        public ?string $observation,
        public ?string $type,
        public ?Contact $sender,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $info = is_array($data['info'] ?? null) ? $data['info'] : [];

        return new self(
            id: JsonHelper::id($info['id'] ?? null) ?? 0,
            number: self::string($info['number'] ?? null),
            date: self::string($info['date'] ?? null),
            hour: self::string($info['hour'] ?? null),
            packages: Packages::fromArray(is_array($info['packages'] ?? null) ? $info['packages'] : []),
            weight: is_numeric($info['weight'] ?? null) ? (float) $info['weight'] : 0.0,
            dimensions: Dimensions::fromArray(is_array($info['dimensions'] ?? null) ? $info['dimensions'] : []),
            pickupDate: self::string($info['pickupDate'] ?? null),
            pickupHours: is_array($info['pickupHours'] ?? null) ? PickupHours::fromArray($info['pickupHours']) : null,
            observation: self::string($info['observation'] ?? null),
            type: self::string($info['type'] ?? null),
            sender: is_array($data['sender'] ?? null) ? Contact::fromArray($data['sender']) : null,
        );
    }

    private static function string(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }
}
