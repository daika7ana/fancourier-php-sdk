<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Order;

use Daika7ana\FanCourier\Support\JsonHelper;

final readonly class OrderEvent
{
    public function __construct(
        public int|string $id,
        public string $name,
        public ?string $date = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $id = $data['id'] ?? null;
        $name = $data['name'] ?? null;
        $date = $data['date'] ?? null;

        return new self(
            id: JsonHelper::id($id) ?? 0,
            name: is_scalar($name) ? (string) $name : '',
            date: is_scalar($date) ? (string) $date : null,
        );
    }
}
