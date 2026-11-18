<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Branch;

use Daika7ana\FanCourier\DTOs\Common\Address;

final readonly class Branch
{
    public function __construct(
        public int|string $id,
        public string $name,
        public ?string $bank = null,
        public ?string $bankAccount = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $secondaryPhone = null,
        public ?string $contactPerson = null,
        public ?Address $address = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $id = $data['id'] ?? null;
        $address = $data['address'] ?? null;

        return new self(
            id: is_scalar($id) ? (is_numeric($id) ? (int) $id : (string) $id) : 0,
            name: is_scalar($data['name'] ?? null) ? (string) $data['name'] : '',
            bank: self::string($data['bank'] ?? null),
            bankAccount: self::string($data['bankAccount'] ?? null),
            email: self::string($data['email'] ?? null),
            phone: self::string($data['phone'] ?? null),
            secondaryPhone: self::string($data['secondaryPhone'] ?? null),
            contactPerson: self::string($data['contactPerson'] ?? null),
            address: is_array($address) ? Address::fromArray($address) : null,
        );
    }

    private static function string(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }
}
