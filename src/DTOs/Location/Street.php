<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Location;

use Daika7ana\FanCourier\Support\JsonHelper;

final readonly class Street
{
    public function __construct(
        public int|string $id,
        public string $street,
        public ?string $type = null,
        public ?string $locality = null,
        public ?string $county = null,
        public ?StreetDetails $details = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $details = $data['details'] ?? null;

        return new self(
            id: JsonHelper::id($data['id'] ?? null) ?? 0,
            street: is_scalar($data['street'] ?? null) ? (string) $data['street'] : '',
            type: self::string($data['type'] ?? null),
            locality: self::string($data['locality'] ?? null),
            county: self::string($data['county'] ?? null),
            details: is_array($details) ? StreetDetails::fromArray($details) : null,
        );
    }

    private static function string(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }
}
