<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Awb;

final readonly class InternalAwbRequest
{
    /**
     * @param list<InternalShipment> $shipments
     */
    public function __construct(
        public array $shipments,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $shipments = [];

        foreach (is_array($data['shipments'] ?? null) ? $data['shipments'] : [] as $shipment) {
            if (is_array($shipment)) {
                $shipments[] = InternalShipment::fromArray($shipment);
            }
        }


        return new self(shipments: $shipments);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'shipments' => array_map(
                static fn(InternalShipment $shipment): array => $shipment->toArray(),
                $this->shipments,
            ),
        ];

        return $data;
    }
}
