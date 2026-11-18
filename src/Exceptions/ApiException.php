<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Exceptions;

use Throwable;

/**
 * FAN Courier answers `{"status":"fail","message":"..."}` for business errors,
 * usually with HTTP 200 but sometimes with 4xx (deleting an archived order is
 * 403); those are surfaced as this exception.
 */
class ApiException extends FanCourierException
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        string $message,
        public readonly array $payload = [],
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message ?: 'The API reported a failure.', $code, $previous);
    }
}
