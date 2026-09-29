<?php

declare(strict_types=1);

namespace App\Modules\Shared\Enums;

enum HealthStatus: string
{
    case Up = 'up';
    case Down = 'down';
}
