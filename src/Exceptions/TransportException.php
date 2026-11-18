<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Exceptions;

use Psr\Http\Client\ClientExceptionInterface;
use Throwable;

class TransportException extends FanCourierException implements ClientExceptionInterface
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message ?: 'Transport error', $code, $previous);
    }
}
