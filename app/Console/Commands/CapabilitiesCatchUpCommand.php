<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Shared\IntegrationEvents\CatchUpIntegrationEvents;
use Illuminate\Console\Command;

/** Pone al día a las capacidades encendidas con los eventos que aún no reconocen. Se programa y se corre al desplegar. */
final class CapabilitiesCatchUpCommand extends Command
{
    protected $signature = 'capabilities:catch-up';

    protected $description = 'Reprocesa los eventos pendientes de las capacidades encendidas (ADR-0007)';

    public function handle(CatchUpIntegrationEvents $catchUp): int
    {
        $this->info(__('capabilities.catch_up.done', ['count' => $catchUp->run()]));

        return self::SUCCESS;
    }
}
