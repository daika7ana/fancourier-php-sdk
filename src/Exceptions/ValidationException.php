<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Exceptions;

use Daika7ana\FanCourier\DTOs\Common\Errors\FanCourierError;
use Exception;
use Psr\Http\Message\ResponseInterface;

class ValidationException extends FanCourierException
{
    /**
     * @param array<string, string[]> $errors
     */
    public function __construct(
        public readonly array $errors,
        string $message = '',
        int $code = 0,
        ?Exception $previous = null,
        public readonly ?ResponseInterface $response = null,
        public readonly ?int $retryAfter = null,
        public readonly ?FanCourierError $error = null,
    ) {
        parent::__construct($message ?: 'Validation failed', $response?->getStatusCode() ?? $code, $previous);
    }
}
