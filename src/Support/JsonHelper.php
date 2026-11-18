<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Support;

use JsonException;

final class JsonHelper
{
    /**
     * @throws JsonException
     * @return array<string, mixed>
     */
    public static function decode(string $json): array
    {
        $decoded = json_decode($json, associative: true, flags: JSON_THROW_ON_ERROR);

        if (!is_array($decoded)) {
            throw new JsonException('Expected a JSON object or array.');
        }

        return $decoded;
    }

    /**
     * Normalizes an id that the API may return as int, numeric string or float.
     */
    public static function id(mixed $value): int|string|null
    {
        return match (true) {
            is_int($value), is_string($value) => $value,
            is_float($value) => (int) $value,
            default => null,
        };
    }

    /**
     * @throws JsonException
     */
    public static function encode(mixed $value): string
    {
        return json_encode($value, flags: JSON_THROW_ON_ERROR);
    }
}
