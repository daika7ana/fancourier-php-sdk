<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Tests\Unit\Config;

use Daika7ana\FanCourier\Config\ClientConfig;
use PHPUnit\Framework\TestCase;

final class ClientConfigTest extends TestCase
{
    private const VARS = [
        'FANCOURIER_BASE_URL',
        'FANCOURIER_CLIENT_ID',
        'FANCOURIER_USERNAME',
        'FANCOURIER_PASSWORD',
        'FANCOURIER_LANGUAGE',
        'FANCOURIER_TIMEOUT',
    ];

    protected function tearDown(): void
    {
        foreach (self::VARS as $name) {
            putenv($name);
        }
    }

    public function testReadsEveryEnvironmentVariable(): void
    {
        putenv('FANCOURIER_BASE_URL=https://example.test/api/');
        putenv('FANCOURIER_CLIENT_ID=7032158');
        putenv('FANCOURIER_USERNAME=user');
        putenv('FANCOURIER_PASSWORD=pass');
        putenv('FANCOURIER_LANGUAGE=en');
        putenv('FANCOURIER_TIMEOUT=5');

        $config = ClientConfig::fromEnvironment();

        self::assertSame('https://example.test/api', $config->baseUrl);
        self::assertSame('7032158', $config->clientId);
        self::assertSame('user', $config->username);
        self::assertSame('pass', $config->password);
        self::assertSame('en', $config->language);
        self::assertSame(5.0, $config->timeout);
    }

    public function testFallsBackToTheDocumentedDefaults(): void
    {
        $config = ClientConfig::fromEnvironment();

        self::assertSame(ClientConfig::BASE_URL_PRODUCTION, $config->baseUrl);
        self::assertNull($config->clientId);
        self::assertSame('ro', $config->language);
        self::assertNull($config->timeout);
    }

    public function testTimeoutDefaultsToNull(): void
    {
        self::assertNull((new ClientConfig())->timeout);
    }
}
