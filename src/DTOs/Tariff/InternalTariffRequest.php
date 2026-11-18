<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Tariff;

use Daika7ana\FanCourier\Support\JsonHelper;
use Daika7ana\FanCourier\DTOs\Common\Dimensions;
use Daika7ana\FanCourier\DTOs\Common\Packages;
use Daika7ana\FanCourier\Enums\AwbOption;
use Daika7ana\FanCourier\Enums\PaymentType;
use Daika7ana\FanCourier\Enums\ServiceType;

final readonly class InternalTariffRequest
{
    /**
     * @param list<AwbOption|string> $options
     */
    public function __construct(
        public ServiceType|string $service,
        public PaymentType|string $payment,
        public float $weight,
        public Packages $packages,
        public ?string $recipientLocality = null,
        public ?string $recipientCounty = null,
        public array $options = [],
        public ?Dimensions $dimensions = null,
        public int|float|null $declaredValue = null,
        public ?string $senderLocality = null,
        public ?string $senderCounty = null,
        public int|string|null $clientId = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $info = is_array($data['info'] ?? null) ? $data['info'] : [];
        $recipient = is_array($data['recipient'] ?? null) ? $data['recipient'] : [];
        $sender = is_array($data['sender'] ?? null) ? $data['sender'] : [];
        $declaredValue = $info['declaredValue'] ?? null;

        return new self(
            service: self::string($info['service'] ?? null) ?? '',
            payment: self::string($info['payment'] ?? null) ?? '',
            weight: is_numeric($info['weight'] ?? null) ? (float) $info['weight'] : 0.0,
            packages: Packages::fromArray(is_array($info['packages'] ?? null) ? $info['packages'] : []),
            recipientLocality: self::string($recipient['locality'] ?? null),
            recipientCounty: self::string($recipient['county'] ?? null),
            options: is_array($info['options'] ?? null) ? array_values($info['options']) : [],
            dimensions: is_array($info['dimensions'] ?? null) ? Dimensions::fromArray($info['dimensions']) : null,
            declaredValue: is_numeric($declaredValue) ? $declaredValue + 0 : null,
            senderLocality: self::string($sender['locality'] ?? null),
            senderCounty: self::string($sender['county'] ?? null),
            clientId: JsonHelper::id($data['clientId'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $info = [
            'service' => $this->service instanceof ServiceType ? $this->service->value : $this->service,
            'payment' => $this->payment instanceof PaymentType ? $this->payment->value : $this->payment,
            'weight' => $this->weight,
            'packages' => $this->packages->toArray(),
        ];

        if ($this->options !== []) {
            $info['options'] = array_map(
                static fn(AwbOption|string $option): string => $option instanceof AwbOption ? $option->value : $option,
                $this->options,
            );
        }

        if ($this->dimensions !== null) {
            $info['dimensions'] = $this->dimensions->toArray();
        }

        if ($this->declaredValue !== null) {
            $info['declaredValue'] = $this->declaredValue;
        }

        $data = ['info' => $info];

        if ($this->clientId !== null) {
            $data = ['clientId' => $this->clientId] + $data;
        }

        if ($this->recipientLocality !== null || $this->recipientCounty !== null) {
            $data['recipient'] = array_filter(
                ['locality' => $this->recipientLocality, 'county' => $this->recipientCounty],
                static fn(?string $value): bool => $value !== null,
            );
        }

        if ($this->senderLocality !== null || $this->senderCounty !== null) {
            $data['sender'] = array_filter(
                ['locality' => $this->senderLocality, 'county' => $this->senderCounty],
                static fn(?string $value): bool => $value !== null,
            );
        }

        return $data;
    }

    private static function string(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }
}
