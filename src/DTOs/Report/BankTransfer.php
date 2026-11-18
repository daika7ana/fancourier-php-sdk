<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Report;

final readonly class BankTransfer
{
    public function __construct(
        public int|string $awbNumber,
        public ?string $awbDate,
        public float $amountCollected,
        public ?string $content,
        public ?string $transferDate,
        public ?string $returnAwbNumber,
        public ?string $reimbursementAwbNumber,
        public ?string $transactionDate,
        public ?string $recipientName,
        public ?string $recipientLocality,
        public ?string $senderName,
        public ?string $senderContactPerson,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $info = is_array($data['info'] ?? null) ? $data['info'] : [];
        $recipient = is_array($data['recipient'] ?? null) ? $data['recipient'] : [];
        $sender = is_array($data['sender'] ?? null) ? $data['sender'] : [];
        $recipientAddress = is_array($recipient['address'] ?? null) ? $recipient['address'] : [];
        $awbNumber = $info['awbNumber'] ?? null;

        return new self(
            awbNumber: is_int($awbNumber) ? $awbNumber : (is_scalar($awbNumber) ? (string) $awbNumber : ''),
            awbDate: self::string($info['awbDate'] ?? null),
            amountCollected: is_numeric($info['amountCollected'] ?? null) ? (float) $info['amountCollected'] : 0.0,
            content: self::string($info['content'] ?? null),
            transferDate: self::string($info['transferDate'] ?? null),
            returnAwbNumber: self::string($info['returnAwbNumber'] ?? null),
            reimbursementAwbNumber: self::string($info['reimbursementAwbNumber'] ?? null),
            transactionDate: self::string($info['transactionDate'] ?? null),
            recipientName: self::string($recipient['name'] ?? null),
            recipientLocality: self::string($recipientAddress['locality'] ?? null),
            senderName: self::string($sender['name'] ?? null),
            senderContactPerson: self::string($sender['contactPerson'] ?? null),
        );
    }

    private static function string(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }
}
