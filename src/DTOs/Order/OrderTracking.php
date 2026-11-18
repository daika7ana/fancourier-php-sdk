<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Order;

use Daika7ana\FanCourier\Support\ApiResponseMapper;

final readonly class OrderTracking
{
    /**
     * @param list<OrderEvent> $events
     */
    public function __construct(
        public int|string $orderId,
        public ?string $orderNumber,
        public array $events,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $orderId = $data['orderId'] ?? null;
        $orderNumber = $data['orderNumber'] ?? null;

        return new self(
            orderId: is_int($orderId) ? $orderId : (is_scalar($orderId) ? (string) $orderId : ''),
            orderNumber: is_scalar($orderNumber) ? (string) $orderNumber : null,
            events: array_map(
                static fn(array $event): OrderEvent => OrderEvent::fromArray($event),
                ApiResponseMapper::listOfArrays($data['events'] ?? null),
            ),
        );
    }
}
