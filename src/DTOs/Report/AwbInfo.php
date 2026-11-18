<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Report;

use Daika7ana\FanCourier\DTOs\Common\Dimensions;
use Daika7ana\FanCourier\DTOs\Common\Packages;

final readonly class AwbInfo
{
    public function __construct(
        public int|string $awbNumber,
        public ?float $weight,
        public Dimensions $dimensions,
        public ?string $payment,
        public ?float $cod,
        public ?float $returnPayment,
        public ?float $declaredValue,
        public ?string $observations,
        public ?string $content,
        public ?string $date,
        public ?string $service,
        public ?float $cost,
        public Packages $packages,
        public ?string $costCenter,
        public ?string $options,
        public ?string $refund,
        public ?string $currency,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $awbNumber = $data['awbNumber'] ?? null;

        return new self(
            awbNumber: is_int($awbNumber) ? $awbNumber : (is_scalar($awbNumber) ? (string) $awbNumber : ''),
            weight: self::float($data['weight'] ?? null),
            dimensions: Dimensions::fromArray(is_array($data['dimensions'] ?? null) ? $data['dimensions'] : []),
            payment: self::string($data['payment'] ?? null),
            cod: self::float($data['cod'] ?? null),
            returnPayment: self::float($data['returnPayment'] ?? null),
            declaredValue: self::float($data['declaredValue'] ?? null),
            observations: self::string($data['observations'] ?? null),
            content: self::string($data['content'] ?? null),
            date: self::string($data['date'] ?? null),
            service: self::string($data['service'] ?? null),
            cost: self::float($data['cost'] ?? null),
            packages: Packages::fromArray(is_array($data['packages'] ?? null) ? $data['packages'] : []),
            costCenter: self::string($data['costCenter'] ?? null),
            options: self::string($data['options'] ?? null),
            refund: self::string($data['refund'] ?? null),
            currency: self::string($data['currency'] ?? null),
        );
    }

    private static function string(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }

    private static function float(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
