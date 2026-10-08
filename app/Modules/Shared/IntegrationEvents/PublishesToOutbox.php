<?php

declare(strict_types=1);

namespace App\Modules\Shared\IntegrationEvents;

use Illuminate\Support\Facades\App;

/**
 * Publica el evento en la bitácora (no con `dispatch()`): así ninguna capacidad apagada pierde lo ocurrido.
 *
 * @phpstan-require-implements IntegrationEvent
 */
trait PublishesToOutbox
{
    public function publish(): void
    {
        App::make(IntegrationEventOutbox::class)->publish($this);
    }
}
