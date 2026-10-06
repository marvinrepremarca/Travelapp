<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Providers;

use App\Modules\Communications\Contracts\MessagingChannel;
use App\Modules\Integrations\Adapters\DatosGov\DatosGovTrmSource;
use App\Modules\Integrations\Adapters\Fake\FakeFlights;
use App\Modules\Integrations\Adapters\Fake\FakeHotels;
use App\Modules\Integrations\Adapters\FakePayments\FakePaymentGateway;
use App\Modules\Integrations\Adapters\FakeWhatsApp\FakeWhatsAppChannel;
use App\Modules\Integrations\Adapters\Null\NullEInvoicingProvider;
use App\Modules\Integrations\Livewire\WhatsAppSimulator;
use App\Modules\Invoicing\Contracts\EInvoicingProvider;
use App\Modules\Payments\Contracts\PaymentGateway;
use App\Modules\Pricing\Contracts\OfficialExchangeRateSource;
use App\Modules\Search\Contracts\FlightProvider;
use App\Modules\Search\Contracts\HotelProvider;
use App\Modules\Shared\Routing\PathPrefix;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

/** Enlaza los puertos de los demás módulos con los adaptadores de proveedores externos. */
final class IntegrationsServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        OfficialExchangeRateSource::class => DatosGovTrmSource::class,
    ];

    public function register(): void
    {
        // Adaptadores por producto (ADR-0006): el agregador de Search usa los activos según configuración.
        $this->app->tag([FakeFlights::class], FlightProvider::TAG);
        $this->app->tag([FakeHotels::class], HotelProvider::TAG);
        $this->app->tag([FakePaymentGateway::class], PaymentGateway::TAG);
        $this->app->tag([NullEInvoicingProvider::class], EInvoicingProvider::TAG);
        $this->app->tag([FakeWhatsAppChannel::class], MessagingChannel::TAG);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'integrations');
        Livewire::component('integrations.whatsapp-simulator', WhatsAppSimulator::class);
        if (! $this->app->routesAreCached()) {
            PathPrefix::load(__DIR__ . '/../Routes/web.php');
        }
    }
}
