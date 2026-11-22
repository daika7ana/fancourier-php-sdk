<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Exceptions;

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
    ) {
        parent::__construct($message, $code, $previous);
    }
}
