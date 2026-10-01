<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Enums;

enum SeatHoldStatus: string
{
    case Held = 'held';
    case Released = 'released';
}
