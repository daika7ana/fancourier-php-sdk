<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Location;

use Daika7ana\FanCourier\Support\JsonHelper;

final readonly class County
{
    public function __construct(
        public int|string $id,
        public string $name,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: JsonHelper::id($data['id'] ?? null) ?? 0,
            name: is_scalar($data['name'] ?? null) ? (string) $data['name'] : '',
        );
    }
}
