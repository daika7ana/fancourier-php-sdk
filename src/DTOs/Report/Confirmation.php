<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Report;

final readonly class Confirmation
{
    public function __construct(
        public ?string $name = null,
        public ?string $date = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: self::string($data['name'] ?? null),
            date: self::string($data['date'] ?? null),
        );
    }

    private static function string(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }
}
