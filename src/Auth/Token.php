<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Auth;

use Daika7ana\FanCourier\Exceptions\AuthenticationException;
use Daika7ana\FanCourier\Support\ApiResponseMapper;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Psr\Http\Message\ResponseInterface;

final readonly class Token
{
    public function __construct(
        public string $accessToken,
        public string $tokenType = 'Bearer',
        public ?DateTimeImmutable $expiresAt = null,
    ) {}

    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        // Treat a token that expires within 60s as expired, so it is not sent mid-flight.
        return $this->expiresAt <= new DateTimeImmutable('+60 seconds');
    }

    /**
     * @return array{access_token: string, token_type: string, expires_at: ?string}
     */
    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'token_type' => $this->tokenType,
            'expires_at' => $this->expiresAt?->format(DATE_ATOM),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $accessToken = (string) ($data['access_token'] ?? '');

        if ($accessToken === '') {
            throw new AuthenticationException('Cannot restore token: access_token is missing or empty.');
        }

        return new self(
            accessToken: $accessToken,
            tokenType: (string) ($data['token_type'] ?? 'Bearer'),
            expiresAt: self::parseDate($data['expires_at'] ?? null),
        );
    }

    /**
     * Build a token from the FAN Courier login payload (`data.token`, `data.expiresAt`).
     *
     * @param array<string, mixed> $data
     */
    public static function fromResponse(array $data, ?ResponseInterface $response = null): self
    {
        $accessToken = (string) ($data['token'] ?? '');

        if ($accessToken === '') {
            throw new AuthenticationException(
                'Cannot build token: token is missing or empty.',
                response: $response,
                retryAfter: $response !== null ? ApiResponseMapper::retryAfter($response) : null,
            );
        }

        return new self(
            accessToken: $accessToken,
            expiresAt: self::parseDate($data['expiresAt'] ?? null),
        );
    }

    private static function parseDate(mixed $value): ?DateTimeImmutable
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        try {
            // FAN sends local Romanian time without an offset; an unqualified parse
            // would land hours off on a UTC server.
            return new DateTimeImmutable($value, new DateTimeZone('Europe/Bucharest'));
        } catch (Exception) {
            // Corrupted cache data: treat as already expired so a fresh token is requested.
            return new DateTimeImmutable();
        }
    }
}
