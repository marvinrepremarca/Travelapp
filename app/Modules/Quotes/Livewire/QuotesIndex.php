<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Livewire;

use App\Modules\Identity\Models\User;
use App\Modules\Quotes\Enums\QuoteStatus;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Shared\Money\MoneyPresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Cotizaciones dentro del alcance del usuario, filtrables por estado y texto. */
#[Layout('components.layouts.backoffice')]
final class QuotesIndex extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Quote::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function render(MoneyPresenter $presenter): View
    {
        // Un estado desconocido en la URL se ignora.
        $status = QuoteStatus::tryFrom($this->status);

        $quotes = Quote::query()
            ->visibleTo($this->actor())
            ->with('customer:id,display_name')
            ->when($status instanceof QuoteStatus, static fn(Builder $query) => $query->where('status', $status))
            ->when($this->search !== '', fn(Builder $query) => $query->where(fn(Builder $inner) => $inner
                ->where('number', 'like', '%' . $this->search . '%')
                ->orWhere('title', 'like', '%' . $this->search . '%')
                ->orWhereHas('customer', fn(Builder $customer) => $customer->where('display_name', 'like', '%' . $this->search . '%'))))
            ->latest('id')
            ->paginate(config()->integer('travel.quotes.per_page'));

        return view('quotes::livewire.quotes-index', [
            'quotes' => $quotes,
            'statuses' => QuoteStatus::cases(),
            'presenter' => $presenter,
        ])->title(__('quotes.title'))
            ->layoutData(['heading' => __('quotes.title')]);
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
