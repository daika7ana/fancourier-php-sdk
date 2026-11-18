<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Awb;

use Daika7ana\FanCourier\Support\JsonHelper;

final readonly class AwbCreationResult
{
    /**
     * @param array<string, mixed>|string|null $errors
     */
    public function __construct(
        public int|string|null $awbNumber,
        public ?float $tariff = null,
        public ?float $vat = null,
        public int $packages = 0,
        public ?string $letter = null,
        public ?string $routingCode = null,
        public ?string $office = null,
        public int|string|null $pickUpPointId = null,
        public ?string $visualCode = null,
        public ?int $estimatedDeliveryTime = null,
        public array|string|null $errors = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            awbNumber: JsonHelper::id($data['awbNumber'] ?? null),
            tariff: is_numeric($data['tariff'] ?? null) ? (float) $data['tariff'] : null,
            vat: is_numeric($data['vat'] ?? null) ? (float) $data['vat'] : null,
            packages: (int) ($data['packages'] ?? 0),
            letter: self::string($data['letter'] ?? null),
            routingCode: self::string($data['routingCode'] ?? null),
            office: self::string($data['office'] ?? null),
            pickUpPointId: JsonHelper::id($data['pickUpPointId'] ?? null),
            visualCode: self::string($data['visualCode'] ?? null),
            estimatedDeliveryTime: is_numeric($data['estimatedDeliveryTime'] ?? null) ? (int) $data['estimatedDeliveryTime'] : null,
            errors: is_array($data['errors'] ?? null)
                ? $data['errors']
                : self::string($data['errors'] ?? null),
        );
    }

    private static function string(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }
}
