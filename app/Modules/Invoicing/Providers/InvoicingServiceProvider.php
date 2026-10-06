<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Providers;

use App\Modules\Invoicing\Events\InvoiceIssued;
use App\Modules\Invoicing\Listeners\ApplyCreditNoteDecision;
use App\Modules\Invoicing\Listeners\SubmitInvoiceToEInvoicing;
use App\Modules\Invoicing\Livewire\InvoiceShow;
use App\Modules\Invoicing\Livewire\InvoicesIndex;
use App\Modules\Invoicing\Services\InvoiceNumbering;
use App\Modules\Shared\Routing\PathPrefix;
use App\Modules\Workflow\Events\ApprovalResolved;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class InvoicingServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $singletons = [
        InvoiceNumbering::class => InvoiceNumbering::class,
    ];

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        if (! $this->app->routesAreCached()) {
            PathPrefix::load(__DIR__ . '/../Routes/web.php');
        }
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'invoicing');

        Livewire::component('invoicing.index', InvoicesIndex::class);
        Livewire::component('invoicing.show', InvoiceShow::class);

        Event::listen(InvoiceIssued::class, SubmitInvoiceToEInvoicing::class);
        Event::listen(ApprovalResolved::class, ApplyCreditNoteDecision::class);
    }
}
