<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Service;

use Daika7ana\FanCourier\Support\JsonHelper;

final readonly class Service
{
    public function __construct(
        public int|string $id,
        public string $name,
        public ?string $description = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: JsonHelper::id($data['id'] ?? null) ?? 0,
            name: (string) ($data['name'] ?? ''),
            description: isset($data['description']) && is_scalar($data['description'])
                ? (string) $data['description']
                : null,
        );
    }
}
