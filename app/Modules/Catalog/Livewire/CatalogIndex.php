<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Livewire;

use App\Modules\Catalog\Models\CatalogProduct;
use App\Modules\Shared\Enums\ProductType;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.backoffice')]
final class CatalogIndex extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $type = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', CatalogProduct::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        // Un tipo desconocido en la URL se ignora.
        $type = ProductType::tryFrom($this->type)->value ?? '';

        $products = CatalogProduct::query()
            ->when($this->search !== '', fn(Builder $query) => $query->where(fn(Builder $inner) => $inner
                ->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('code', 'like', '%' . $this->search . '%')
                ->orWhere('destination_city', 'like', '%' . $this->search . '%')))
            ->when($type !== '', static fn(Builder $query) => $query->where('product_type', $type))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(config()->integer('travel.catalog.per_page'));

        return view('catalog::livewire.catalog-index', [
            'products' => $products,
            'types' => CatalogProduct::OWN_PRODUCT_TYPES,
            'canManage' => Gate::allows('manage', CatalogProduct::class),
        ])->title(__('catalog.title'))
            ->layoutData(['heading' => __('catalog.title')]);
    }
}
