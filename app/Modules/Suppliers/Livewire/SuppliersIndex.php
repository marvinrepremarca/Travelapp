<?php

declare(strict_types=1);

namespace App\Modules\Suppliers\Livewire;

use App\Modules\Suppliers\Models\Supplier;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.backoffice')]
final class SuppliersIndex extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: false)]
    public bool $onlyAttention = false;

    public function mount(): void
    {
        Gate::authorize('viewAny', Supplier::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedOnlyAttention(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $today = CarbonImmutable::today();
        $warningDays = config()->integer('travel.suppliers.rnt_expiry_warning_days');

        $suppliers = Supplier::query()
            ->when($this->search !== '', fn(Builder $query) => $query->where(fn(Builder $inner) => $inner
                ->where('trade_name', 'like', '%' . $this->search . '%')
                ->orWhere('legal_name', 'like', '%' . $this->search . '%')
                ->orWhere('tax_id', 'like', '%' . $this->search . '%')))
            // Requieren atención: inactivos o con RNT exigido que falta o vence dentro del aviso.
            ->when($this->onlyAttention, static fn(Builder $query) => $query->where(static fn(Builder $inner) => $inner
                ->where('is_active', false)
                ->orWhere(static fn(Builder $rnt) => $rnt
                    ->where('is_tourism_provider', true)
                    ->where('country', Supplier::RNT_COUNTRY)
                    ->where(static fn(Builder $expiry) => $expiry->whereNull('rnt_expires_on')->orWhere('rnt_expires_on', '<=', $today->addDays($warningDays)->toDateString())))))
            ->orderBy('trade_name')
            ->paginate(config()->integer('travel.suppliers.per_page'));

        return view('suppliers::livewire.suppliers-index', [
            'suppliers' => $suppliers,
            'today' => $today,
            'warningDays' => $warningDays,
            'canManage' => Gate::allows('manage', Supplier::class),
        ])->title(__('suppliers.title'))
            ->layoutData(['heading' => __('suppliers.title')]);
    }
}
