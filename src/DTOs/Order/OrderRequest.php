<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Order;

use Daika7ana\FanCourier\DTOs\Common\Contact;

final readonly class OrderRequest
{
    public function __construct(
        public OrderInfo $info,
        public ?Contact $recipient = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            info: OrderInfo::fromArray(is_array($data['info'] ?? null) ? $data['info'] : []),
            recipient: is_array($data['recipient'] ?? null) ? Contact::fromArray($data['recipient']) : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = ['info' => $this->info->toArray()];

        if ($this->recipient !== null) {
            $data['recipient'] = $this->recipient->toArray();
        }

        return $data;
    }
}
