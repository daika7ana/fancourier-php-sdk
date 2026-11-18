<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Tariff;

use Daika7ana\FanCourier\DTOs\Common\Dimensions;
use Daika7ana\FanCourier\DTOs\Common\Packages;
use Daika7ana\FanCourier\Enums\ContentType;
use Daika7ana\FanCourier\Enums\DeliveryMode;
use Daika7ana\FanCourier\Enums\ServiceType;

final readonly class ExternalTariffRequest
{
    public function __construct(
        public ServiceType|string $service,
        public DeliveryMode|string $deliveryMode,
        public ContentType|string $documentType,
        public float $weight,
        public Dimensions $dimensions,
        public Packages $packages,
        public ?string $recipientCountry = null,
        public ?string $senderLocality = null,
        public ?string $senderCounty = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $info = is_array($data['info'] ?? null) ? $data['info'] : [];
        $recipient = is_array($data['recipient'] ?? null) ? $data['recipient'] : [];
        $sender = is_array($data['sender'] ?? null) ? $data['sender'] : [];

        return new self(
            service: self::string($info['service'] ?? null) ?? '',
            deliveryMode: self::string($info['deliveryMode'] ?? null) ?? '',
            documentType: self::string($info['documentType'] ?? null) ?? '',
            weight: is_numeric($info['weight'] ?? null) ? (float) $info['weight'] : 0.0,
            dimensions: Dimensions::fromArray(is_array($info['dimensions'] ?? null) ? $info['dimensions'] : []),
            packages: Packages::fromArray(is_array($info['packages'] ?? null) ? $info['packages'] : []),
            recipientCountry: self::string($recipient['country'] ?? null),
            senderLocality: self::string($sender['locality'] ?? null),
            senderCounty: self::string($sender['county'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $info = [
            'service' => $this->service instanceof ServiceType ? $this->service->value : $this->service,
            'deliveryMode' => $this->deliveryMode instanceof DeliveryMode ? $this->deliveryMode->value : $this->deliveryMode,
            'documentType' => $this->documentType instanceof ContentType ? $this->documentType->value : $this->documentType,
            'weight' => $this->weight,
            'dimensions' => $this->dimensions->toArray(),
            'packages' => $this->packages->toArray(),
        ];

        $data = ['info' => $info];

        if ($this->recipientCountry !== null) {
            $data['recipient'] = ['country' => $this->recipientCountry];
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
