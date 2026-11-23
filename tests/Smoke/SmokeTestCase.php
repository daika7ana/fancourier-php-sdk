<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Tests\Smoke;

use Daika7ana\FanCourier\Client;
use Daika7ana\FanCourier\Config\ClientConfig;
use PHPUnit\Framework\TestCase;

abstract class SmokeTestCase extends TestCase
{
    private ?Client $client = null;

    protected function client(): Client
    {
        if ($this->client instanceof Client) {
            return $this->client;
        }

        $config = ClientConfig::fromEnvironment();

        if ($config->username === '' || $config->password === '') {
            $this->markTestSkipped('Set FANCOURIER_USERNAME and FANCOURIER_PASSWORD to run the smoke tests.');
        }

        $client = Client::create(config: $config);
        $client->authenticate($config->username, $config->password);

        return $this->client = $client;
    }

    /**
     * Endpoints that report on the contract need the selfAWB clientId.
     */
    protected function clientWithClientId(): Client
    {
        $client = $this->client();

        if ($client->getConfig()->clientId === null || $client->getConfig()->clientId === '') {
            $this->markTestSkipped('Set FANCOURIER_CLIENT_ID to run the contract-scoped smoke tests.');
        }

        return $client;
    }

    /**
     * Write tests create real records on the production API, so they are opt-in.
     */
    protected function requiresWrites(): void
    {
        if (filter_var(getenv('FANCOURIER_SMOKE_ENABLE_WRITES'), FILTER_VALIDATE_BOOLEAN) !== true) {
            $this->markTestSkipped('Set FANCOURIER_SMOKE_ENABLE_WRITES=true to run the write smoke tests.');
        }
    }
}
