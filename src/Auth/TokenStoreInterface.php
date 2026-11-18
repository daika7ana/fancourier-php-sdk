<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Auth;

interface TokenStoreInterface
{
    public function getToken(): ?Token;

    public function setToken(Token $token): void;
}
