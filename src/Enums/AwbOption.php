<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Enums;

/**
 * Option letters accepted in `info.options` on AWB creation.
 */
enum AwbOption: string
{
    case OpeningOnDelivery = 'A';
    case OPod = 'B';
    case FanOfficeDropOff = 'C';
    case FanOfficePickUp = 'D';
    case PayPointDropOff = 'E';
    case PayPointPickUp = 'F';
    case SmsBusiness = 'M';
    case PickUpPrealert = 'O';
    case Prealert = 'P';
    case SaturdayDelivery = 'S';
    case FanboxPickUp = 'V';
    case FanboxDropOff = 'W';
    case EPod = 'X';
    case MPos = 'Y';
}
