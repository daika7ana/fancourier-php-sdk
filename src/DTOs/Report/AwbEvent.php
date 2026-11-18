<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Report;

use Daika7ana\FanCourier\Support\JsonHelper;

final readonly class AwbEvent
{
    public function __construct(
        public int|string $id,
        public string $name,
        public ?string $location = null,
        public ?string $date = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $id = $data['id'] ?? null;
        $name = $data['name'] ?? null;

        return new self(
            id: JsonHelper::id($id) ?? '',
            name: is_scalar($name) ? (string) $name : '',
            location: self::string($data['location'] ?? null),
            date: self::string($data['date'] ?? null),
        );
    }

    private static function string(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }
}
