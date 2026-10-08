<?php

declare(strict_types=1);

namespace App\Modules\Customers\Providers;

use App\Modules\Customers\Contracts\CustomerContacts;
use App\Modules\Customers\Contracts\CustomerOrigins;
use App\Modules\Customers\Livewire\CustomerForm;
use App\Modules\Customers\Livewire\CustomerShow;
use App\Modules\Customers\Livewire\CustomersIndex;
use App\Modules\Customers\Livewire\TravelerForm;
use App\Modules\Customers\Models\Customer;
use App\Modules\Customers\Models\CustomerConsent;
use App\Modules\Customers\Models\Traveler;
use App\Modules\Customers\Policies\CustomerPolicy;
use App\Modules\Customers\Services\EloquentCustomerContacts;
use App\Modules\Customers\Services\NullCustomerOrigins;
use App\Modules\Shared\Routing\PathPrefix;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

/** Núcleo: maestro de clientes, viajeros y consentimientos que comparten todas las capacidades (ADR-0007). */
final class CustomersServiceProvider extends ServiceProvider
{
    /**
     * Nombres con los que la auditoría ya guardó estos modelos antes de pasar al núcleo: se conservan para que
     * el historial existente siga apuntando a ellos sin reescribir datos.
     */
    private const LEGACY_MORPH_NAMES = [
        'App\Modules\Crm\Models\Customer' => Customer::class,
        'App\Modules\Crm\Models\CustomerConsent' => CustomerConsent::class,
        'App\Modules\Crm\Models\Traveler' => Traveler::class,
    ];

    /** @var array<class-string, class-string> */
    public array $singletons = [
        CustomerContacts::class => EloquentCustomerContacts::class,
    ];

    public function register(): void
    {
        // Sin una capacidad que origine clientes, el formulario funciona solo. Comercial la reemplaza al registrarse.
        $this->app->singletonIf(CustomerOrigins::class, NullCustomerOrigins::class);
    }

    public function boot(): void
    {
        Relation::morphMap(self::LEGACY_MORPH_NAMES);

        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        if (! $this->app->routesAreCached()) {
            PathPrefix::load(__DIR__ . '/../Routes/web.php');
        }
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'customers');

        Livewire::component('customers.customers-index', CustomersIndex::class);
        Livewire::component('customers.customer-form', CustomerForm::class);
        Livewire::component('customers.customer-show', CustomerShow::class);
        Livewire::component('customers.traveler-form', TravelerForm::class);

        Gate::policy(Customer::class, CustomerPolicy::class);
    }
}
