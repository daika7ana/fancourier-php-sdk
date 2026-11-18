<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Enums;

enum PickupPointType: string
{
    case Fanbox = 'fanbox';
    case PayPoint = 'paypoint';
    case Office = 'office';
}
