<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Awb;

use Daika7ana\FanCourier\DTOs\Common\Contact;

final readonly class InternalShipment
{
    public function __construct(
        public ShipmentInfo $info,
        public Contact $recipient,
        public ?Contact $sender = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            info: ShipmentInfo::fromArray(is_array($data['info'] ?? null) ? $data['info'] : []),
            recipient: Contact::fromArray(is_array($data['recipient'] ?? null) ? $data['recipient'] : []),
            sender: is_array($data['sender'] ?? null) ? Contact::fromArray($data['sender']) : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'info' => $this->info->toArray(),
            'recipient' => $this->recipient->toArray(),
        ];

        if ($this->sender !== null) {
            $data['sender'] = $this->sender->toArray();
        }

        return $data;
    }
}
