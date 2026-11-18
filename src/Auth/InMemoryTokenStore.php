<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Auth;

final class InMemoryTokenStore implements TokenStoreInterface
{
    private ?Token $token = null;

    public function getToken(): ?Token
    {
        return $this->token;
    }

    public function setToken(Token $token): void
    {
        $this->token = $token;
    }
}
