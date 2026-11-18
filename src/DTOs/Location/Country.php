<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Location;

final readonly class Country
{
    public function __construct(
        public int|string $id,
        public string $name,
        public ?string $code = null,
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
            code: self::code($data),
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function code(array $data): ?string
    {
        foreach (['code', 'iso', 'isoCode'] as $key) {
            if (is_scalar($data[$key] ?? null)) {
                return (string) $data[$key];
            }
        }

        return null;
    }
}
