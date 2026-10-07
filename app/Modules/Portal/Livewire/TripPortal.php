<?php

declare(strict_types=1);

namespace App\Modules\Portal\Livewire;

use App\Modules\Bookings\Contracts\TravelerTrips;
use App\Modules\Bookings\Data\Trip;
use App\Modules\Payments\Contracts\CustomerPayments;
use App\Modules\Portal\Services\TripLinks;
use App\Modules\Shared\Money\MoneyPresenter;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * "Mi viaje": itinerario día a día, PDF del itinerario y vouchers, estado de cuenta y pago del saldo en línea.
 * Se entra con el enlace firmado; las descargas reciben enlaces firmados con la misma vigencia.
 */
#[Layout('components.layouts.portal')]
final class TripPortal extends Component
{
    #[Locked]
    public string $bookingUlid = '';

    #[Locked]
    public int $expires = 0;

    public function mount(string $booking): void
    {
        abort_unless(app(TravelerTrips::class)->trip($booking) instanceof Trip, 404);
        $this->bookingUlid = $booking;
        $this->expires = (int) request()->query('expires', (string) CarbonImmutable::now()->timestamp);
    }

    public function pay(CustomerPayments $payments): void
    {
        $url = $payments->payBalanceUrl($this->bookingUlid);
        if ($url === null) {
            $this->addError('pay', __('portal.nothing_to_pay'));

            return;
        }

        $this->redirect($url);
    }

    public function render(TravelerTrips $trips, CustomerPayments $payments, TripLinks $links, MoneyPresenter $presenter): View
    {
        $trip = $trips->trip($this->bookingUlid) ?? abort(404);
        $expiresAt = CarbonImmutable::createFromTimestamp($this->expires);

        return view('portal::livewire.trip', [
            'trip' => $trip,
            'statement' => $payments->statement($this->bookingUlid),
            'itineraryUrl' => $links->itinerary($trip->ulid, $expiresAt),
            'voucherUrls' => collect($trip->services)->filter->hasVoucher()->mapWithKeys(static fn($service): array => [$service->ulid => $links->voucher($trip->ulid, $service->ulid, $expiresAt)])->all(),
            'presenter' => $presenter,
        ])->title(__('portal.title', ['number' => $trip->number]));
    }
}
