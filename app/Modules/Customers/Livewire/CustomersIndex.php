<?php

declare(strict_types=1);

namespace App\Modules\Customers\Livewire;

use App\Modules\Customers\Enums\CustomerType;
use App\Modules\Customers\Enums\DocumentType;
use App\Modules\Customers\Models\Customer;
use App\Modules\Customers\Services\CustomerDocumentGuard;
use App\Modules\Identity\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Listado de clientes por alcance. Busca por nombre, correo o teléfono; el documento
 * se busca por coincidencia exacta con su huella (nunca se descifra para buscar).
 */
#[Layout('components.layouts.backoffice')]
final class CustomersIndex extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $type = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function render(CustomerDocumentGuard $documents): View
    {
        $term = trim($this->search);

        $customers = Customer::query()
            ->visibleTo($this->actor())
            ->when(CustomerType::tryFrom($this->type), static fn(Builder $query, CustomerType $type) => $query->where('type', $type))
            ->when($term !== '', fn(Builder $query) => $query->where(fn(Builder $inner) => $this->applySearch($inner, $term, $documents)))
            ->orderBy('display_name')
            ->paginate(config()->integer('travel.crm.per_page'));

        return view('customers::livewire.customers-index', [
            'customers' => $customers,
            'types' => CustomerType::cases(),
        ])->title(__('customers.title'))
            ->layoutData(['heading' => __('customers.title')]);
    }

    /** @param Builder<Customer> $query */
    private function applySearch(Builder $query, string $term, CustomerDocumentGuard $documents): void
    {
        $query->where('display_name', 'like', "%{$term}%")
            ->orWhere('email', 'like', '%' . mb_strtolower($term) . '%')
            ->orWhere('phone', 'like', "%{$term}%");

        foreach (DocumentType::cases() as $documentType) {
            $query->orWhere(static fn(Builder $byDocument) => $byDocument
                ->where('document_type', $documentType)
                ->where('document_hash', $documents->hashFor($documentType->value, $documentType->normalize($term))));
        }
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
