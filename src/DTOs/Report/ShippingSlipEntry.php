<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Report;

use Daika7ana\FanCourier\DTOs\Common\Contact;

final readonly class ShippingSlipEntry
{
    public function __construct(
        public AwbInfo $info,
        public ?Contact $recipient = null,
        public ?Contact $sender = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            info: AwbInfo::fromArray(is_array($data['info'] ?? null) ? $data['info'] : []),
            recipient: is_array($data['recipient'] ?? null) ? Contact::fromArray($data['recipient']) : null,
            sender: is_array($data['sender'] ?? null) ? Contact::fromArray($data['sender']) : null,
        );
    }
}
