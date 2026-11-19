<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Awb;

use Daika7ana\FanCourier\Support\JsonHelper;

final readonly class InternalAwbRequest
{
    /**
     * @param list<InternalShipment> $shipments
     */
    public function __construct(
        public array $shipments,
        public int|string|null $platformId = null,
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


        return new self(
            shipments: $shipments,
            platformId: JsonHelper::id($data['platformId'] ?? null),
        );
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

        if ($this->platformId !== null) {
            $data['platformId'] = $this->platformId;
        }

        return $data;
    }
}
