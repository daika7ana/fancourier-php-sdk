<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Common;

use Daika7ana\SimpleCollections\Collection;

/**
 * FAN Courier report endpoints return items together with pagination counters.
 *
 * @template TValue
 */
final readonly class PaginatedResult
{
    /**
     * @param Collection<int, TValue> $items
     */
    public function __construct(
        public Collection $items,
        public ?int $perPage = null,
        public ?int $currentPage = null,
        public ?int $total = null,
    ) {}

    /**
     * @template TItem
     *
     * @param array<string, mixed> $payload
     * @param callable(array<string, mixed>): TItem $mapper
     *
     * @return PaginatedResult<TItem>
     */
    public static function fromArray(array $payload, callable $mapper): PaginatedResult
    {
        $items = [];

        foreach (is_array($payload['data'] ?? null) ? $payload['data'] : [] as $item) {
            if (is_array($item)) {
                $items[] = $mapper($item);
            }
        }

        return new PaginatedResult(
            items: new Collection($items),
            perPage: isset($payload['perPage']) && is_numeric($payload['perPage']) ? (int) $payload['perPage'] : null,
            currentPage: isset($payload['currentPage']) && is_numeric($payload['currentPage']) ? (int) $payload['currentPage'] : null,
            total: isset($payload['total']) && is_numeric($payload['total']) ? (int) $payload['total'] : null,
        );
    }
}
