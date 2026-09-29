<?php

declare(strict_types=1);

namespace App\Modules\Organization\Contracts;

use App\Modules\Shared\Enums\VisibilityScope;

/** Parámetros de negocio ⚙ de la agencia. Única vía para leerlos desde otros módulos. */
interface AppSettings
{
    public function agencyTimezone(): string;

    public function defaultCurrency(): string;

    public function quoteValidityHours(): int;

    public function onRequestResponseSlaHours(): int;

    public function travelAgentScope(): VisibilityScope;

    public function hideMarginsFromAgents(): bool;
}
