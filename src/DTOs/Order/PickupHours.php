<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Order;

final readonly class PickupHours
{
    public function __construct(
        public string $first,
        public string $second,
    ) {}

    /**
     * Accepts both the order payload (`first`/`second`) and the report shape
     * (`firstHour`/`secondHour`).
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            first: self::string($data['first'] ?? $data['firstHour'] ?? null),
            second: self::string($data['second'] ?? $data['secondHour'] ?? null),
        );
    }

    /**
     * @return array{first: string, second: string}
     */
    public function toArray(): array
    {
        return [
            'first' => $this->first,
            'second' => $this->second,
        ];
    }

    private static function string(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
