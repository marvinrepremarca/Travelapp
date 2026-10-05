<?php

declare(strict_types=1);

namespace App\Modules\Finance\Livewire;

use App\Modules\Finance\Actions\SettleSupplierPayablesAction;
use App\Modules\Finance\Enums\PayableStatus;
use App\Modules\Finance\Models\SupplierPayable;
use App\Modules\Identity\Models\User;
use App\Modules\Shared\Enums\Permission;
use App\Modules\Shared\Exceptions\BusinessRuleException;
use App\Modules\Shared\Money\MoneyPresenter;
use App\Modules\Suppliers\Models\Supplier;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Cuentas por pagar a proveedores: vencimientos, totales por proveedor y liquidación con comprobante (solo finanzas). */
#[Layout('components.layouts.backoffice')]
final class PayablesIndex extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $supplier = '';

    #[Url(except: 'open')]
    public string $status = 'open';

    #[Url(except: false)]
    public bool $onlyOverdue = false;

    /** @var list<string> */
    public array $selected = [];

    public string $paymentReference = '';

    public function mount(): void
    {
        $this->authorizeFinance();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['supplier', 'status', 'onlyOverdue'], true)) {
            $this->resetPage();
            $this->reset('selected');
        }
    }

    public function settle(SettleSupplierPayablesAction $settle, MoneyPresenter $presenter): void
    {
        $this->authorizeFinance();
        $this->validate([
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['string'],
            'paymentReference' => ['required', 'string', 'max:100'],
        ], attributes: ['selected' => __('finance.payables.selection'), 'paymentReference' => __('finance.payables.payment_reference')]);

        try {
            $total = $settle->execute($this->actor(), $this->selected, $this->paymentReference, CarbonImmutable::now());
        } catch (BusinessRuleException $violation) {
            $this->addError('selected', $violation->getMessage());

            return;
        }

        session()->flash('status', __('finance.payables.settled', ['amount' => $presenter->format($total)]));
        $this->reset('selected', 'paymentReference');
    }

    public function render(MoneyPresenter $presenter): View
    {
        $today = CarbonImmutable::parse(CarbonImmutable::now(config()->string('travel.agency.timezone'))->toDateString());
        $status = PayableStatus::tryFrom($this->status);

        $payables = SupplierPayable::query()
            ->with('supplier:id,trade_name')
            ->when($status instanceof PayableStatus, static fn(Builder $query) => $query->where('status', $status))
            ->when($this->supplier !== '', fn(Builder $query) => $query->where('supplier_id', (int) $this->supplier))
            ->when($this->onlyOverdue, static fn(Builder $query) => $query->where('status', PayableStatus::Open)->where('due_date', '<', $today->toDateString()))
            ->orderBy('due_date')
            ->paginate(config()->integer('travel.finance.per_page'));

        $openTotals = SupplierPayable::query()
            ->where('status', PayableStatus::Open)
            ->toBase()
            ->selectRaw('supplier_id, currency, sum(amount_minor) as total, count(*) as items, min(due_date) as next_due')
            ->groupBy('supplier_id', 'currency')
            ->get();

        return view('finance::livewire.payables-index', [
            'payables' => $payables,
            'openTotals' => $openTotals->map(static fn(object $row): array => [
                'supplier_id' => (int) $row->supplier_id,
                'total' => Money::ofMinor((int) $row->total, (string) $row->currency),
                'items' => (int) $row->items,
                'next_due' => CarbonImmutable::parse((string) $row->next_due),
            ])->all(),
            'suppliers' => Supplier::query()->withTrashed()->orderBy('trade_name')->pluck('trade_name', 'id')->all(),
            'statuses' => PayableStatus::cases(),
            'today' => $today,
            'presenter' => $presenter,
        ])->title(__('finance.payables.title'))
            ->layoutData(['heading' => __('finance.payables.title')]);
    }

    private function authorizeFinance(): void
    {
        abort_unless($this->actor()->can(Permission::FinanceAccess->value), 403);
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
