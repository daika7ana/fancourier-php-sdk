<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\PickupPoint;

final readonly class PickupDrawer
{
    public function __construct(
        public ?string $type = null,
        public int $number = 0,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: is_scalar($data['type'] ?? null) ? (string) $data['type'] : null,
            number: is_numeric($data['number'] ?? null) ? (int) $data['number'] : 0,
        );
    }
}
