<?php

declare(strict_types=1);

namespace App\Modules\Finance\Providers;

use App\Modules\Bookings\Events\BookingItemCancelled;
use App\Modules\Bookings\Events\BookingItemConfirmed;
use App\Modules\Finance\Contracts\CashRegister;
use App\Modules\Finance\Listeners\RegisterSupplierPayable;
use App\Modules\Finance\Listeners\VoidSupplierPayable;
use App\Modules\Finance\Livewire\BankAccountForm;
use App\Modules\Finance\Livewire\BankAccountsIndex;
use App\Modules\Finance\Livewire\CashRegisterScreen;
use App\Modules\Finance\Livewire\PayablesIndex;
use App\Modules\Finance\Livewire\ProfitabilityScreen;
use App\Modules\Finance\Livewire\ReconciliationScreen;
use App\Modules\Finance\Services\CashDesk;
use App\Modules\Finance\Services\NullCashRegister;
use App\Modules\Shared\Capabilities\Capabilities;
use App\Modules\Shared\Enums\Capability;
use App\Modules\Shared\Enums\CatchUpPolicy;
use App\Modules\Shared\IntegrationEvents\CapabilitySubscriptions;
use App\Modules\Shared\Routing\PathPrefix;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class FinanceServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $singletons = [
    ];

    public function register(): void
    {
        // Contratos que consumen otras capacidades: con Contabilidad apagada se entrega la implementación nula (ADR-0007).
        Capabilities::bindContract($this->app, Capability::Accounting, CashRegister::class, CashDesk::class, NullCashRegister::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        if (! $this->app->routesAreCached()) {
            PathPrefix::load(__DIR__ . '/../Routes/web.php', Capability::Accounting);
        }
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'finance');

        Livewire::component('finance.payables', PayablesIndex::class);
        Livewire::component('finance.cash', CashRegisterScreen::class);
        Livewire::component('finance.profitability', ProfitabilityScreen::class);
        Livewire::component('finance.bank-accounts', BankAccountsIndex::class);
        Livewire::component('finance.bank-account-form', BankAccountForm::class);
        Livewire::component('finance.reconciliation', ReconciliationScreen::class);

        // Las obligaciones con proveedores siguen la vida de los servicios del expediente.
        // Con Contabilidad apagada quedan en la bitácora y se registran al encenderla (ADR-0007).
        $subscriptions = $this->app->make(CapabilitySubscriptions::class);
        $subscriptions->listen(Capability::Accounting, BookingItemConfirmed::class, RegisterSupplierPayable::class, CatchUpPolicy::Replay);
        $subscriptions->listen(Capability::Accounting, BookingItemCancelled::class, VoidSupplierPayable::class, CatchUpPolicy::Replay);
    }
}
