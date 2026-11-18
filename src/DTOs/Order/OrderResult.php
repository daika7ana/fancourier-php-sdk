<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Order;

use Daika7ana\FanCourier\Support\JsonHelper;

final readonly class OrderResult
{
    public function __construct(
        public int|string $id,
    ) {}

    /**
     * Accepts the full payload (`data.id`) as well as the bare data block (`id`).
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $id = is_array($data['data'] ?? null) ? ($data['data']['id'] ?? null) : ($data['id'] ?? null);

        return new self(id: JsonHelper::id($id) ?? 0);
    }
}
