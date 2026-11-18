<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Enums;

/**
 * Values returned by `GET /reports/services`.
 */
enum ServiceType: string
{
    case Standard = 'Standard';
    case RedCode = 'RedCode';
    case ContColector = 'Cont Colector';
    case ExpressLoco2H = 'Express Loco 2H';
    case ExpressLoco4H = 'Express Loco 4H';
    case ExpressLoco6H = 'Express Loco 6H';
    case Export = 'Export';
    case RedCodeContColector = 'Red code-Cont Colector';
    case ExpressLoco2HContColector = 'Express Loco 2H-Cont Colector';
    case ExpressLoco4HContColector = 'Express Loco 4H-Cont Colector';
    case ExpressLoco6HContColector = 'Express Loco 6H-Cont Colector';
    case ExpressLoco1H = 'Express Loco 1H';
    case ExpressLoco1HContColector = 'Express Loco 1H-Cont Colector';
    case ExportContColector = 'Export-Cont Colector';
    case CollectPoint = 'CollectPoint';
    case CollectPointContColector = 'CollectPoint Cont Colector';
    case ProduseAlbe = 'Produse Albe';
    case ProduseAlbeContColector = 'Produse Albe-Cont Colector';
    case TransportMarfa = 'Transport Marfa';
    case TransportMarfaContColector = 'Transport Marfa-Cont Colector';
    case TransportMarfaProduseAlbe = 'Transport Marfa Produse Albe';
    case TransportMarfaProduseAlbeContColector = 'Transport Marfa Produse Albe-Cont Colector';
    case Fanbox = 'FANbox';
    case FanboxContColector = 'FANbox Cont Colector';
}
