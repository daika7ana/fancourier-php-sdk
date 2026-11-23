<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Tests\Smoke;

final class ClientSmokeTest extends SmokeTestCase
{
    public function testAuthenticatesAgainstLiveApi(): void
    {
        $token = $this->client()->getToken();

        $this->assertNotNull($token);
        $this->assertNotSame('', $token->accessToken);
    }
}
