<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Exceptions;

use Daika7ana\FanCourier\DTOs\Common\Errors\FanCourierError;
use Psr\Http\Message\ResponseInterface;

class UnexpectedStatusException extends FanCourierException
{
    public function __construct(
        public readonly ?ResponseInterface $response = null,
        string $message = '',
        int $code = 0,
        public readonly ?int $retryAfter = null,
        public readonly ?FanCourierError $error = null,
    ) {
        if ($message === '') {
            $message = $response === null
                ? 'Unexpected HTTP status'
                : "Unexpected HTTP status {$response->getStatusCode()}";
        }

        parent::__construct($message, $response?->getStatusCode() ?? $code);
    }
}
