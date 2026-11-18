<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Enums;

enum PaymentType: string
{
    case Sender = 'expeditor';
    case Recipient = 'destinatar';
    case Other = 'Altul';
}
