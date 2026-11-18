<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Location;

final readonly class ExternalLocality
{
    public function __construct(
        public int|string $id,
        public string $name,
        public ?string $county = null,
        public ?string $country = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $id = $data['id'] ?? null;

        return new self(
            id: is_scalar($id) ? (is_numeric($id) ? (int) $id : (string) $id) : 0,
            name: is_scalar($data['name'] ?? null) ? (string) $data['name'] : '',
            county: is_scalar($data['county'] ?? null) ? (string) $data['county'] : null,
            country: is_scalar($data['country'] ?? null) ? (string) $data['country'] : null,
        );
    }
}
