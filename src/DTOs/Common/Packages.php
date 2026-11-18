<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Common;

final readonly class Packages
{
    public function __construct(
        public int $parcel = 0,
        public int $envelope = 0,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        // The API documented `envelopes` once and later corrected it to `envelope`.
        return new self(
            parcel: (int) ($data['parcel'] ?? 0),
            envelope: (int) ($data['envelope'] ?? $data['envelopes'] ?? 0),
        );
    }

    /**
     * @return array{parcel: int, envelope: int}
     */
    public function toArray(): array
    {
        return [
            'parcel' => $this->parcel,
            'envelope' => $this->envelope,
        ];
    }
}
