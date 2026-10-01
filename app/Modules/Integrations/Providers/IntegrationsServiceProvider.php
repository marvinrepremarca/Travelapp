<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Providers;

use App\Modules\Integrations\Adapters\DatosGov\DatosGovTrmSource;
use App\Modules\Pricing\Contracts\OfficialExchangeRateSource;
use Illuminate\Support\ServiceProvider;

/** Enlaza los puertos de los demás módulos con los adaptadores de proveedores externos. */
final class IntegrationsServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        OfficialExchangeRateSource::class => DatosGovTrmSource::class,
    ];

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }
}
