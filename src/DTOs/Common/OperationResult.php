<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Common;

/**
 * Result of an endpoint that only reports success (delete AWB, delete order).
 */
final readonly class OperationResult
{
    /**
     * @param array<string, mixed> $rawData
     */
    public function __construct(
        public string $message,
        public array $rawData = [],
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $message = $data['data'] ?? $data['message'] ?? null;

        if (is_array($message)) {
            $message = $message['message'] ?? '';
        }

        return new self(
            message: is_string($message) ? $message : '',
            rawData: $data,
        );
    }
}
