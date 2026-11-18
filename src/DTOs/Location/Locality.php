<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Location;

use Daika7ana\FanCourier\Support\JsonHelper;

final readonly class Locality
{
    public function __construct(
        public int|string $id,
        public string $name,
        public ?string $county = null,
        public ?string $agency = null,
        public ?int $exteriorKm = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: JsonHelper::id($data['id'] ?? null) ?? 0,
            name: is_scalar($data['name'] ?? null) ? (string) $data['name'] : '',
            county: is_scalar($data['county'] ?? null) ? (string) $data['county'] : null,
            agency: is_scalar($data['agency'] ?? null) ? (string) $data['agency'] : null,
            exteriorKm: is_numeric($data['exteriorKm'] ?? null) ? (int) $data['exteriorKm'] : null,
        );
    }
}
