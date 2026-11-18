<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Common;

final readonly class Dimensions
{
    public function __construct(
        public float $length,
        public float $width,
        public float $height,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            length: self::toFloat($data['length'] ?? null),
            width: self::toFloat($data['width'] ?? null),
            height: self::toFloat($data['height'] ?? null),
        );
    }

    /**
     * @return array{length: float, width: float, height: float}
     */
    public function toArray(): array
    {
        return [
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }

    private static function toFloat(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }
}
