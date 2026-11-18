<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Awb;

final readonly class AwbResult
{
    /**
     * @param list<AwbCreationResult> $results
     * @param array<array-key, mixed> $rawData
     */
    public function __construct(
        public array $results,
        public array $rawData = [],
    ) {}

    /**
     * @param array<array-key, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $results = [];
        // /intern-awb wraps the list in `response`; /extern-awb answers with a bare list.
        $items = array_is_list($data) ? $data : ($data['response'] ?? $data['data'] ?? []);

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
