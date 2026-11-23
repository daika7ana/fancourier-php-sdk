<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Exceptions;

use Daika7ana\FanCourier\DTOs\Common\Errors\FanCourierError;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class AuthenticationException extends FanCourierException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        public readonly ?ResponseInterface $response = null,
        public readonly ?int $retryAfter = null,
        public readonly ?FanCourierError $error = null,
    ) {
        parent::__construct($message, $response?->getStatusCode() ?? $code, $previous);
    }
}
