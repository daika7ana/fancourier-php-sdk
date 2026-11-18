<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Common;

/**
 * Address shape shared by AWB sender/recipient, orders, branches and PUDO points.
 */
final readonly class Address
{
    public function __construct(
        public ?string $county = null,
        public ?string $locality = null,
        public ?string $street = null,
        public ?string $streetNo = null,
        public ?string $zipCode = null,
        public ?string $building = null,
        public ?string $entrance = null,
        public ?string $floor = null,
        public ?string $apartment = null,
        public ?string $country = null,
        public ?string $region = null,
        public ?string $pickupLocationId = null,
        public ?string $dropOffLocationId = null,
        public ?string $agency = null,
        public ?string $reference = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            county: self::string($data['county'] ?? null),
            locality: self::string($data['locality'] ?? null),
            street: self::string($data['street'] ?? null),
            streetNo: self::string($data['streetNo'] ?? null),
            zipCode: self::string($data['zipCode'] ?? $data['zipcode'] ?? null),
            building: self::string($data['building'] ?? null),
            entrance: self::string($data['entrance'] ?? null),
            floor: self::string($data['floor'] ?? null),
            apartment: self::string($data['apartment'] ?? null),
            country: self::string($data['country'] ?? null),
            region: self::string($data['region'] ?? null),
            pickupLocationId: self::string($data['pickupLocationId'] ?? null),
            dropOffLocationId: self::string($data['dropOffLocationId'] ?? null),
            agency: self::string($data['agency'] ?? null),
            reference: self::string($data['reference'] ?? null),
        );
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return array_filter(
            [
                'county' => $this->county,
                'locality' => $this->locality,
                'street' => $this->street,
                'streetNo' => $this->streetNo,
                'zipCode' => $this->zipCode,
                'building' => $this->building,
                'entrance' => $this->entrance,
                'floor' => $this->floor,
                'apartment' => $this->apartment,
                'country' => $this->country,
                'region' => $this->region,
                'pickupLocationId' => $this->pickupLocationId,
                'dropOffLocationId' => $this->dropOffLocationId,
                'agency' => $this->agency,
                'reference' => $this->reference,
            ],
            static fn(?string $value): bool => $value !== null && $value !== '',
        );
    }

    private static function string(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }
}
