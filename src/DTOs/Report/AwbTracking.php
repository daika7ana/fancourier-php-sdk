<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Report;

use Daika7ana\FanCourier\Support\JsonHelper;
use Daika7ana\FanCourier\Support\ApiResponseMapper;

final readonly class AwbTracking
{
    /**
     * @param list<AwbEvent> $events
     */
    public function __construct(
        public ?string $content,
        public int|string $awbNumber,
        public ?string $date,
        public ?string $paymentDate,
        public ?string $returnAwbNumber,
        public ?string $redirectionAwbNumber,
        public ?string $reimbursementAwbNumber,
        public ?string $oPODAwbNumber,
        public ?Confirmation $confirmation,
        public ?string $otd,
        public array $events,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $awbNumber = $data['awbNumber'] ?? null;

        return new self(
            content: self::string($data['content'] ?? null),
            awbNumber: JsonHelper::id($awbNumber) ?? '',
            date: self::string($data['date'] ?? null),
            paymentDate: self::string($data['paymentDate'] ?? null),
            returnAwbNumber: self::string($data['returnAwbNumber'] ?? null),
            redirectionAwbNumber: self::string($data['redirectionAwbNumber'] ?? null),
            reimbursementAwbNumber: self::string($data['reimbursementAwbNumber'] ?? null),
            oPODAwbNumber: self::string($data['oPODAwbNumber'] ?? null),
            confirmation: is_array($data['confirmation'] ?? null) ? Confirmation::fromArray($data['confirmation']) : null,
            otd: self::string($data['OTD'] ?? $data['otd'] ?? null),
            events: array_map(
                static fn(array $event): AwbEvent => AwbEvent::fromArray($event),
                ApiResponseMapper::listOfArrays($data['events'] ?? null),
            ),
        );
    }

    private static function string(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }
}
