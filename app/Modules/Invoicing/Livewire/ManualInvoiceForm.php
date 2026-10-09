<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Livewire;

use App\Modules\Customers\Models\Customer;
use App\Modules\Identity\Models\User;
use App\Modules\Invoicing\Actions\IssueManualInvoiceAction;
use App\Modules\Invoicing\Data\ManualInvoiceLine;
use App\Modules\Invoicing\Enums\InvoiceLineKind;
use App\Modules\Shared\Enums\Permission;
use App\Modules\Shared\Enums\ProductType;
use App\Modules\Shared\Exceptions\BusinessRuleException;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Factura a un cliente sin expediente (ADR-0007): ventas que no pasaron por Reservas. */
#[Layout('components.layouts.backoffice')]
final class ManualInvoiceForm extends Component
{
    public string $customerUlid = '';

    public string $customerSearch = '';

    /** @var list<array{description: string, kind: string, product_type: string, amount: string}> */
    public array $lines = [];

    public function mount(): void
    {
        $this->authorizeFinance();
        $this->addLine();
    }

    public function chooseCustomer(string $ulid): void
    {
        $this->customerUlid = $ulid;
        $this->customerSearch = '';
    }

    public function addLine(): void
    {
        $this->lines[] = ['description' => '', 'kind' => InvoiceLineKind::OwnIncome->value, 'product_type' => '', 'amount' => ''];
    }

    public function removeLine(int $index): void
    {
        $this->lines = array_values(array_filter($this->lines, static fn(int $position): bool => $position !== $index, ARRAY_FILTER_USE_KEY));
    }

    public function issue(IssueManualInvoiceAction $issue): void
    {
        $this->authorizeFinance();
        $validated = $this->validate([
            'customerUlid' => ['required', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.kind' => ['required', Rule::enum(InvoiceLineKind::class)],
            'lines.*.product_type' => ['nullable', Rule::enum(ProductType::class)],
            'lines.*.amount' => ['required', 'numeric', 'gt:0'],
        ], attributes: Arr::dot(trans('invoicing.manual.fields')));

        $customer = $this->visibleCustomers()->where('ulid', $validated['customerUlid'])->first();
        if (! $customer instanceof Customer) {
            $this->addError('customerUlid', __('invoicing.manual.customer_not_found'));

            return;
        }

        $currency = config()->string('travel.agency.default_currency');
        try {
            $invoice = $issue->execute($this->actor(), $customer, $currency, array_map(static fn(array $line): ManualInvoiceLine => new ManualInvoiceLine(
                $line['description'],
                InvoiceLineKind::from($line['kind']),
                Money::of($line['amount'], $currency),
                ProductType::tryFrom((string) ($line['product_type'] ?? '')),
            ), array_values($validated['lines'])), CarbonImmutable::now());
        } catch (BusinessRuleException $violation) {
            $this->addError('lines', $violation->getMessage());

            return;
        }

        session()->flash('status', __('invoicing.manual.issued', ['number' => $invoice->number]));
        $this->redirectRoute('invoicing.show', $invoice, navigate: true);
    }

    public function render(): View
    {
        $selected = $this->customerUlid === '' ? null : $this->visibleCustomers()->where('ulid', $this->customerUlid)->first(['id', 'ulid', 'display_name']);
        $matches = $this->customerSearch === '' ? collect() : $this->visibleCustomers()
            ->where('display_name', 'like', '%' . $this->customerSearch . '%')
            ->orderBy('display_name')
            ->limit(config()->integer('travel.invoicing.per_page'))
            ->get(['id', 'ulid', 'display_name']);
        $title = __('invoicing.manual.title');

        return view('invoicing::livewire.manual-invoice-form', [
            'selected' => $selected,
            'matches' => $matches,
            'kinds' => InvoiceLineKind::cases(),
            'productTypes' => ProductType::cases(),
            'currency' => config()->string('travel.agency.default_currency'),
        ])->title($title)->layoutData(['heading' => $title]);
    }

    /** @return Builder<Customer> */
    private function visibleCustomers(): Builder
    {
        return Customer::query()->visibleTo($this->actor());
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
