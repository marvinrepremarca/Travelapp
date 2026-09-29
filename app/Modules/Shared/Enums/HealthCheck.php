<?php

declare(strict_types=1);

namespace App\Modules\Shared\Enums;

enum HealthCheck: string
{
    case Database = 'database';
    case Cache = 'cache';
}
