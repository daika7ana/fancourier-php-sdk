<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Config;

final readonly class ClientConfig
{
    public const BASE_URL_PRODUCTION = 'https://api.fancourier.ro';

    public function __construct(
        public string $baseUrl = self::BASE_URL_PRODUCTION,
        public int|string|null $clientId = null,
        public string $username = '',
        public string $password = '',
        public string $language = 'ro',
        public ?float $timeout = null,
    ) {}

    public static function fromEnvironment(): self
    {
        $baseUrl = getenv('FANCOURIER_BASE_URL');
        $clientId = getenv('FANCOURIER_CLIENT_ID');
        $language = getenv('FANCOURIER_LANGUAGE');
        $timeout = getenv('FANCOURIER_TIMEOUT');

        return new self(
            baseUrl: is_string($baseUrl) && $baseUrl !== '' ? rtrim($baseUrl, '/') : self::BASE_URL_PRODUCTION,
            clientId: is_string($clientId) && $clientId !== '' ? $clientId : null,
            username: (string) (getenv('FANCOURIER_USERNAME') ?: ''),
            password: (string) (getenv('FANCOURIER_PASSWORD') ?: ''),
            language: is_string($language) && $language !== '' ? $language : 'ro',
            timeout: is_string($timeout) && is_numeric($timeout) ? (float) $timeout : null,
        );
    }
}
