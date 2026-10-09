<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Livewire;

use App\Modules\Bookings\Actions\CreateDirectBookingAction;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Customers\Models\Customer;
use App\Modules\Identity\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Expediente directo para un cliente, sin cotización previa (ADR-0007). */
#[Layout('components.layouts.backoffice')]
final class DirectBookingForm extends Component
{
    public string $customerUlid = '';

    public string $customerSearch = '';

    public string $title = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Booking::class);
    }

    public function chooseCustomer(string $ulid): void
    {
        $this->customerUlid = $ulid;
        $this->customerSearch = '';
    }

    public function save(CreateDirectBookingAction $create): void
    {
        Gate::authorize('viewAny', Booking::class);
        $validated = $this->validate([
            'customerUlid' => ['required', 'string'],
            'title' => ['required', 'string', 'max:255'],
        ], attributes: Arr::dot(trans('bookings.direct.fields')));

        $customer = $this->visibleCustomers()->where('ulid', $validated['customerUlid'])->first();
        if (! $customer instanceof Customer) {
            $this->addError('customerUlid', __('bookings.direct.customer_not_found'));

            return;
        }

        $booking = $create->execute($this->actor(), $customer, $validated['title'], config()->string('travel.agency.default_currency'));

        session()->flash('status', __('bookings.direct.created', ['number' => $booking->number]));
        $this->redirectRoute('bookings.items.create', $booking, navigate: true);
    }

    public function render(): View
    {
        $selected = $this->customerUlid === '' ? null : $this->visibleCustomers()->where('ulid', $this->customerUlid)->first(['id', 'ulid', 'display_name']);
        $matches = $this->customerSearch === '' ? collect() : $this->visibleCustomers()
            ->where('display_name', 'like', '%' . $this->customerSearch . '%')
            ->orderBy('display_name')
            ->limit(config()->integer('travel.bookings.per_page'))
            ->get(['id', 'ulid', 'display_name']);
        $title = __('bookings.direct.title');

        return view('bookings::livewire.direct-booking-form', [
            'selected' => $selected,
            'matches' => $matches,
        ])->title($title)->layoutData(['heading' => $title]);
    }

    /** @return Builder<Customer> */
    private function visibleCustomers(): Builder
    {
        return Customer::query()->visibleTo($this->actor());
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
