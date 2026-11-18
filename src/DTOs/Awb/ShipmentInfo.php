<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Awb;

use Daika7ana\FanCourier\DTOs\Common\Dimensions;
use Daika7ana\FanCourier\DTOs\Common\Packages;
use Daika7ana\FanCourier\Enums\AwbOption;
use Daika7ana\FanCourier\Enums\PaymentType;
use Daika7ana\FanCourier\Enums\ServiceType;

final readonly class ShipmentInfo
{
    /**
     * @param list<AwbOption|string> $options
     */
    public function __construct(
        public ServiceType|string $service,
        public Packages $packages,
        public float $weight,
        public PaymentType|string $payment,
        public Dimensions $dimensions,
        public ?string $bank = null,
        public ?string $bankAccount = null,
        public int|float|null $cod = null,
        public int|float|null $declaredValue = null,
        public ?string $refund = null,
        public ?string $returnPayment = null,
        public ?string $observation = null,
        public ?string $content = null,
        public ?string $costCenter = null,
        public array $options = [],
        public ?string $uitCode = null,
        public ?bool $isValueUnderThreshold = null,
        public ?string $countryCode = null,
        public ?string $vatId = null,
        public ?string $company = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            service: self::string($data['service'] ?? null) ?? '',
            packages: Packages::fromArray(is_array($data['packages'] ?? null) ? $data['packages'] : []),
            weight: is_numeric($data['weight'] ?? null) ? (float) $data['weight'] : 0.0,
            payment: self::string($data['payment'] ?? null) ?? '',
            dimensions: Dimensions::fromArray(is_array($data['dimensions'] ?? null) ? $data['dimensions'] : []),
            bank: self::string($data['bank'] ?? null),
            bankAccount: self::string($data['bankAccount'] ?? null),
            cod: self::number($data['cod'] ?? null),
            declaredValue: self::number($data['declaredValue'] ?? null),
            refund: self::string($data['refund'] ?? null),
            returnPayment: self::string($data['returnPayment'] ?? null),
            observation: self::string($data['observation'] ?? null),
            content: self::string($data['content'] ?? null),
            costCenter: self::string($data['costCenter'] ?? null),
            options: is_array($data['options'] ?? null) ? array_values($data['options']) : [],
            uitCode: self::string($data['uitCode'] ?? null),
            isValueUnderThreshold: isset($data['isValueUnderThreshold']) ? (bool) $data['isValueUnderThreshold'] : null,
            countryCode: self::string($data['countryCode'] ?? null),
            vatId: self::string($data['vatId'] ?? null),
            company: self::string($data['company'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'service' => $this->service instanceof ServiceType ? $this->service->value : $this->service,
            'packages' => $this->packages->toArray(),
            'weight' => $this->weight,
            'payment' => $this->payment instanceof PaymentType ? $this->payment->value : $this->payment,
            'dimensions' => $this->dimensions->toArray(),
        ];

        $this->add($data, 'bank', $this->bank);
        $this->add($data, 'bankAccount', $this->bankAccount);
        $this->add($data, 'cod', $this->cod);
        $this->add($data, 'declaredValue', $this->declaredValue);
        $this->add($data, 'refund', $this->refund);
        $this->add($data, 'returnPayment', $this->returnPayment);
        $this->add($data, 'observation', $this->observation);
        $this->add($data, 'content', $this->content);
        $this->add($data, 'costCenter', $this->costCenter);

        if ($this->options !== []) {
            $data['options'] = array_map(
                static fn(AwbOption|string $option): string => $option instanceof AwbOption ? $option->value : $option,
                $this->options,
            );
        }

        $this->add($data, 'uitCode', $this->uitCode);
        $this->add($data, 'isValueUnderThreshold', $this->isValueUnderThreshold);
        $this->add($data, 'countryCode', $this->countryCode);
        $this->add($data, 'vatId', $this->vatId);
        $this->add($data, 'company', $this->company);

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
