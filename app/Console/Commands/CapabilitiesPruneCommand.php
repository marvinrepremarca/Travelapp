<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Shared\IntegrationEvents\PruneIntegrationEvents;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/** Aplica la retención de la bitácora de integración; se programa a diario. */
final class CapabilitiesPruneCommand extends Command
{
    protected $signature = 'capabilities:prune';

    protected $description = 'Borra los eventos de integración ya entregados o vencidos (ADR-0007)';

    public function handle(PruneIntegrationEvents $prune): int
    {
        $this->info(__('capabilities.prune.done', ['count' => $prune->run(CarbonImmutable::now())]));

        return self::SUCCESS;
    }
}
