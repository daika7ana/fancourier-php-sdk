<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Tariff;

final readonly class Tariff
{
    public function __construct(
        public ?float $extraKmCost = null,
        public ?float $weightCost = null,
        public ?float $insuranceCost = null,
        public ?float $optionsCost = null,
        public ?float $fuelCost = null,
        public ?float $costNoVAT = null,
        public ?float $vat = null,
        public ?float $total = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            extraKmCost: self::float($data['extraKmCost'] ?? null),
            weightCost: self::float($data['weightCost'] ?? null),
            insuranceCost: self::float($data['insuranceCost'] ?? null),
            optionsCost: self::float($data['optionsCost'] ?? null),
            fuelCost: self::float($data['fuelCost'] ?? null),
            costNoVAT: self::float($data['costNoVAT'] ?? null),
            vat: self::float($data['vat'] ?? null),
            total: self::float($data['total'] ?? null),
        );
    }

    private static function float(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
