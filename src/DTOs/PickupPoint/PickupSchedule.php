<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\PickupPoint;

final readonly class PickupSchedule
{
    public function __construct(
        public ?string $firstHour = null,
        public ?string $secondHour = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            firstHour: is_scalar($data['firstHour'] ?? null) ? (string) $data['firstHour'] : null,
            secondHour: is_scalar($data['secondHour'] ?? null) ? (string) $data['secondHour'] : null,
        );
    }
}
