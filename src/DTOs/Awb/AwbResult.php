<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Awb;

final readonly class AwbResult
{
    /**
     * @param list<AwbCreationResult> $results
     * @param array<string, mixed> $rawData
     */
    public function __construct(
        public array $results,
        public array $rawData = [],
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $results = [];
        $items = $data['response'] ?? $data['data'] ?? [];

        foreach (is_array($items) ? $items : [] as $item) {
            if (is_array($item)) {
                $results[] = AwbCreationResult::fromArray($item);
            }
        }

        return new self(results: $results, rawData: $data);
    }

    public function first(): ?AwbCreationResult
    {
        return $this->results[0] ?? null;
    }
}
