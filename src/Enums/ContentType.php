<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Enums;

enum ContentType: string
{
    case Document = 'document';
    case NonDocument = 'non document';
}
