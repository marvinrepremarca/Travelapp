<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Providers;

use App\Modules\Invoicing\Contracts\InvoicingMetrics;
use App\Modules\Invoicing\Events\InvoiceIssued;
use App\Modules\Invoicing\Listeners\ApplyCreditNoteDecision;
use App\Modules\Invoicing\Listeners\SubmitInvoiceToEInvoicing;
use App\Modules\Invoicing\Livewire\InvoiceShow;
use App\Modules\Invoicing\Livewire\InvoicesIndex;
use App\Modules\Invoicing\Livewire\ManualInvoiceForm;
use App\Modules\Invoicing\Services\EloquentInvoicingMetrics;
use App\Modules\Invoicing\Services\InvoiceNumbering;
use App\Modules\Invoicing\Services\NullInvoicingMetrics;
use App\Modules\Shared\Capabilities\Capabilities;
use App\Modules\Shared\Enums\Capability;
use App\Modules\Shared\Enums\CatchUpPolicy;
use App\Modules\Shared\IntegrationEvents\CapabilitySubscriptions;
use App\Modules\Shared\Routing\PathPrefix;
use App\Modules\Workflow\Events\ApprovalResolved;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class InvoicingServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $singletons = [
        InvoiceNumbering::class => InvoiceNumbering::class,
    ];

    public function register(): void
    {
        // Indicadores para los tableros: con Facturación apagada se entrega la implementación nula (ADR-0007).
        Capabilities::bindContract($this->app, Capability::Invoicing, InvoicingMetrics::class, EloquentInvoicingMetrics::class, NullInvoicingMetrics::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        if (! $this->app->routesAreCached()) {
            PathPrefix::load(__DIR__ . '/../Routes/web.php', Capability::Invoicing);
        }
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'invoicing');

        Livewire::component('invoicing.index', InvoicesIndex::class);
        Livewire::component('invoicing.manual', ManualInvoiceForm::class);
        Livewire::component('invoicing.show', InvoiceShow::class);

        $subscriptions = $this->app->make(CapabilitySubscriptions::class);
        $subscriptions->listen(Capability::Invoicing, InvoiceIssued::class, SubmitInvoiceToEInvoicing::class, CatchUpPolicy::Replay);
        $subscriptions->listen(Capability::Invoicing, ApprovalResolved::class, ApplyCreditNoteDecision::class, CatchUpPolicy::Replay);
    }
}
