<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Awb;

use Daika7ana\FanCourier\DTOs\Common\Dimensions;
use Daika7ana\FanCourier\DTOs\Common\Packages;
use Daika7ana\FanCourier\Enums\AwbOption;
use Daika7ana\FanCourier\Enums\ContentType;
use Daika7ana\FanCourier\Enums\DeliveryMode;
use Daika7ana\FanCourier\Enums\PaymentType;
use Daika7ana\FanCourier\Enums\ServiceType;

final readonly class ExternalShipmentInfo
{
    /**
     * @param list<AwbOption|string> $options
     */
    public function __construct(
        public ServiceType|string $service,
        public DeliveryMode|string $deliveryMode,
        public ContentType|string $contentType,
        public Packages $packages,
        public Dimensions $dimensions,
        public float $weight,
        public ?string $bank = null,
        public ?string $bankAccount = null,
        public int|float|null $cod = null,
        public int|float|null $declaredValue = null,
        public PaymentType|string|null $payment = null,
        public ?string $observation = null,
        public ?string $costCenter = null,
        public array $options = [],
        public ?string $uitCode = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            service: self::string($data['service'] ?? null) ?? '',
            deliveryMode: self::string($data['deliveryMode'] ?? null) ?? '',
            contentType: self::string($data['contentType'] ?? null) ?? '',
            packages: Packages::fromArray(is_array($data['packages'] ?? null) ? $data['packages'] : []),
            dimensions: Dimensions::fromArray(is_array($data['dimensions'] ?? null) ? $data['dimensions'] : []),
            weight: is_numeric($data['weight'] ?? null) ? (float) $data['weight'] : 0.0,
            bank: self::string($data['bank'] ?? null),
            bankAccount: self::string($data['bankAccount'] ?? null),
            cod: self::number($data['cod'] ?? null),
            declaredValue: self::number($data['declaredValue'] ?? null),
            payment: self::string($data['payment'] ?? null),
            observation: self::string($data['observation'] ?? null),
            costCenter: self::string($data['costCenter'] ?? null),
            options: is_array($data['options'] ?? null) ? array_values($data['options']) : [],
            uitCode: self::string($data['uitCode'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'service' => $this->service instanceof ServiceType ? $this->service->value : $this->service,
            'deliveryMode' => $this->deliveryMode instanceof DeliveryMode ? $this->deliveryMode->value : $this->deliveryMode,
            'contentType' => $this->contentType instanceof ContentType ? $this->contentType->value : $this->contentType,
            'packages' => $this->packages->toArray(),
            'dimensions' => $this->dimensions->toArray(),
            'weight' => $this->weight,
        ];

        $this->add($data, 'bank', $this->bank);
        $this->add($data, 'bankAccount', $this->bankAccount);
        $this->add($data, 'cod', $this->cod);
        $this->add($data, 'declaredValue', $this->declaredValue);

        if ($this->payment !== null) {
            $data['payment'] = $this->payment instanceof PaymentType ? $this->payment->value : $this->payment;
        }

        $this->add($data, 'observation', $this->observation);
        $this->add($data, 'costCenter', $this->costCenter);

        if ($this->options !== []) {
            $data['options'] = array_map(
                static fn(AwbOption|string $option): string => $option instanceof AwbOption ? $option->value : $option,
                $this->options,
            );
        }

        $this->add($data, 'uitCode', $this->uitCode);

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function add(array &$data, string $key, mixed $value): void
    {
        if ($value !== null) {
            $data[$key] = $value;
        }
    }

    private static function string(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }

    private static function number(mixed $value): int|float|null
    {
        return is_numeric($value) ? $value + 0 : null;
    }
}
