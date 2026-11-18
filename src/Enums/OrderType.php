<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Enums;

enum OrderType: string
{
    case Standard = 'Standard';
    case ExpressLoco1H = 'Express Loco 1h';
    case ExpressLoco2H = 'Express Loco 2h';
    case ExpressLoco4H = 'Express Loco 4h';
    case ExpressLoco6H = 'Express Loco 6h';
}
