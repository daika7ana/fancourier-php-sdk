<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Location;

final readonly class StreetDetails
{
    public function __construct(
        public ?string $fromNo = null,
        public ?string $toNo = null,
        public ?string $parityNo = null,
        public ?string $zipCode = null,
        public ?string $visualCode = null,
        public ?string $routingCode = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            fromNo: self::string($data['fromNo'] ?? null),
            toNo: self::string($data['toNo'] ?? null),
            parityNo: self::string($data['parityNo'] ?? null),
            zipCode: self::string($data['zipCode'] ?? null),
            visualCode: self::string($data['visualCode'] ?? null),
            routingCode: self::string($data['routingCode'] ?? null),
        );
    }

    private static function string(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }
}
