<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Shared\Capabilities\Capabilities;
use App\Modules\Shared\Enums\Capability;
use Illuminate\Console\Command;

/** Muestra qué capacidades están encendidas; con --check falla si hay dependencias incumplidas (paso de despliegue). */
final class CapabilitiesStatusCommand extends Command
{
    protected $signature = 'capabilities:status {--check : Termina con error si la configuración es inválida}';

    protected $description = 'Estado de las capacidades de negocio activables (ADR-0007)';

    public function handle(Capabilities $capabilities): int
    {
        $this->table(
            [__('capabilities.status.capability'), __('capabilities.status.state'), __('capabilities.status.requires')],
            array_map(static fn(Capability $c): array => [
                $c->label(),
                __($capabilities->enabled($c) ? 'capabilities.status.on' : 'capabilities.status.off'),
                implode(', ', array_map(static fn(Capability $r): string => $r->label(), $c->requires())),
            ], Capability::cases()),
        );

        $problems = $capabilities->problems();
        foreach ($problems as $problem) {
            $this->error($problem);
        }

        if ($problems === []) {
            $this->info(__('capabilities.status.ok'));

            return self::SUCCESS;
        }

        return $this->option('check') === true ? self::FAILURE : self::SUCCESS;
    }
}
