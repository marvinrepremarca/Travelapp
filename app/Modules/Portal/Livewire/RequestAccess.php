<?php

declare(strict_types=1);

namespace App\Modules\Portal\Livewire;

use App\Modules\Bookings\Contracts\TravelerTrips;
use App\Modules\Bookings\Data\Trip;
use App\Modules\Crm\Contracts\CustomerContacts;
use App\Modules\Portal\Services\TripAccessNotifier;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Recuperar el acceso a "Mi viaje": número de expediente + documento del titular. Si coinciden, el enlace se envía al
 * WhatsApp registrado. La respuesta es siempre la misma (no revela si el expediente existe) y tiene límite de intentos.
 */
#[Layout('components.layouts.portal')]
final class RequestAccess extends Component
{
    private const LIMITER = 'portal-access:';

    private const SECONDS_PER_HOUR = 3600;

    public string $bookingNumber = '';

    public string $documentNumber = '';

    public bool $requested = false;

    public function send(TravelerTrips $trips, CustomerContacts $contacts, TripAccessNotifier $notifier): void
    {
        $this->validate([
            'bookingNumber' => ['required', 'string', 'max:30'],
            'documentNumber' => ['required', 'string', 'max:30'],
        ], attributes: ['bookingNumber' => __('portal.access.booking_number'), 'documentNumber' => __('portal.access.document_number')]);

        $key = self::LIMITER . request()->ip();
        if (RateLimiter::tooManyAttempts($key, config()->integer('travel.portal.access_attempts_per_hour'))) {
            $this->addError('bookingNumber', __('portal.access.too_many'));

            return;
        }

        RateLimiter::hit($key, self::SECONDS_PER_HOUR);
        $trip = $trips->findByNumber($this->bookingNumber);
        if ($trip instanceof Trip && $contacts->documentMatches($trip->customerId, $this->documentNumber)) {
            $notifier->send($trip, 'trip_portal_access:' . $trip->ulid . ':' . CarbonImmutable::now()->format('YmdH'));
        }

        $this->reset('bookingNumber', 'documentNumber');
        $this->requested = true;
    }

    public function render(): View
    {
        return view('portal::livewire.request-access')->title(__('portal.access.title'));
    }
}
