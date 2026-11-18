<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Common;

/**
 * Sender/recipient block used by AWB, courier orders and reports.
 */
final readonly class Contact
{
    public function __construct(
        public ?string $name = null,
        public ?string $contactPerson = null,
        public ?string $phone = null,
        public ?string $secondaryPhone = null,
        public ?string $email = null,
        public ?Address $address = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: self::string($data['name'] ?? null),
            contactPerson: self::string($data['contactPerson'] ?? null),
            phone: self::string($data['phone'] ?? null),
            secondaryPhone: self::string($data['secondaryPhone'] ?? null),
            email: self::string($data['email'] ?? null),
            address: is_array($data['address'] ?? null) ? Address::fromArray($data['address']) : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = array_filter(
            [
                'name' => $this->name,
                'contactPerson' => $this->contactPerson,
                'phone' => $this->phone,
                'secondaryPhone' => $this->secondaryPhone,
                'email' => $this->email,
            ],
            static fn(?string $value): bool => $value !== null && $value !== '',
        );

        if ($this->address !== null) {
            $data['address'] = $this->address->toArray();
        }

        return $data;
    }

    private static function string(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }
}
