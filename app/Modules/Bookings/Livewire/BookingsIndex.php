<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Livewire;

use App\Modules\Bookings\Enums\BookingStatus;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Identity\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Expedientes dentro del alcance del usuario, filtrables por estado y texto. */
#[Layout('components.layouts.backoffice')]
final class BookingsIndex extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Booking::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $status = BookingStatus::tryFrom($this->status);

        $bookings = Booking::query()
            ->visibleTo($this->actor())
            ->with('customer:id,display_name')
            ->when($status instanceof BookingStatus, static fn(Builder $query) => $query->where('status', $status))
            ->when($this->search !== '', fn(Builder $query) => $query->where(fn(Builder $inner) => $inner
                ->where('number', 'like', '%' . $this->search . '%')
                ->orWhere('title', 'like', '%' . $this->search . '%')
                ->orWhere('quote_number', 'like', '%' . $this->search . '%')
                ->orWhereHas('customer', fn(Builder $customer) => $customer->where('display_name', 'like', '%' . $this->search . '%'))))
            ->latest('id')
            ->paginate(config()->integer('travel.bookings.per_page'));

        return view('bookings::livewire.bookings-index', [
            'bookings' => $bookings,
            'statuses' => BookingStatus::cases(),
        ])->title(__('bookings.title'))
            ->layoutData(['heading' => __('bookings.title')]);
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
