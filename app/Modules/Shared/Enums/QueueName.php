<?php

declare(strict_types=1);

namespace App\Modules\Shared\Enums;

/** Colas por prioridad de negocio (Horizon en producción). */
enum QueueName: string
{
    /** Cobros y webhooks de pasarelas: lo más crítico. */
    case Payments = 'payments';
    case Default = 'default';
}
