<?php

declare(strict_types=1);

namespace App\Modules\Shared\Enums;

/** Resultado de entregar un evento de integración a un suscriptor. */
enum DeliveryOutcome: string
{
    case Delivered = 'delivered';
    case Skipped = 'skipped';
}
