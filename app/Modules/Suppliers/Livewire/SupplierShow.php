<?php

declare(strict_types=1);

namespace App\Modules\Suppliers\Livewire;

use App\Modules\Identity\Models\User;
use App\Modules\Shared\Enums\Permission;
use App\Modules\Shared\Enums\ProductType;
use App\Modules\Shared\ValueObjects\Percentage;
use App\Modules\Suppliers\Actions\AddBankAccountAction;
use App\Modules\Suppliers\Actions\AddCommissionAction;
use App\Modules\Suppliers\Actions\AddContactAction;
use App\Modules\Suppliers\Actions\EndCommissionAction;
use App\Modules\Suppliers\Actions\RevealBankAccountAction;
use App\Modules\Suppliers\Actions\SetSupplierActiveAction;
use App\Modules\Suppliers\Enums\BankAccountType;
use App\Modules\Suppliers\Enums\CommissionBase;
use App\Modules\Suppliers\Exceptions\SupplierRuleViolation;
use App\Modules\Suppliers\Models\Supplier;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.backoffice')]
final class SupplierShow extends Component
{
    #[Locked]
    public string $supplierUlid;

    /** @var array<string, string> */
    public array $contact = ['name' => '', 'position' => '', 'email' => '', 'phone' => ''];

    /** @var array<string, string> */
    public array $commission = ['product_type' => '', 'rate' => '', 'base' => 'gross', 'valid_from' => '', 'valid_until' => ''];

    /** @var array<string, string> */
    public array $account = ['bank_name' => '', 'account_type' => 'checking', 'number' => '', 'holder_name' => '', 'currency' => 'COP'];

    /**
     * Números de cuenta revelados en esta vista; no se guardan.
     *
     * @var array<string, string>
     */
    public array $revealed = [];

    public string $revealReason = '';

    public function mount(Supplier $supplier): void
    {
        Gate::authorize('view', $supplier);
        $this->supplierUlid = $supplier->ulid;
    }

    public function toggleActive(SetSupplierActiveAction $setActive): void
    {
        Gate::authorize('manage', Supplier::class);
        $supplier = $this->supplier();
        $setActive->execute($supplier, ! $supplier->is_active);
    }

    public function addContact(AddContactAction $add): void
    {
        Gate::authorize('manage', Supplier::class);
        $data = $this->validate([
            'contact.name' => ['required', 'string', 'max:255'],
            'contact.position' => ['nullable', 'string', 'max:100'],
            'contact.email' => ['nullable', 'email', 'max:255'],
            'contact.phone' => ['nullable', 'string', 'max:30'],
        ], attributes: $this->prefixed('contact', 'suppliers.contact_fields'))['contact'];

        $add->execute($this->supplier(), $data['name'], $data['position'] ?: null, $data['email'] ?: null, $data['phone'] ?: null);
        $this->reset('contact');
    }

    public function addCommission(AddCommissionAction $add): void
    {
        Gate::authorize('manage', Supplier::class);
        $data = $this->validate([
            'commission.product_type' => ['required', Rule::enum(ProductType::class)],
            'commission.rate' => ['required', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
            'commission.base' => ['required', Rule::enum(CommissionBase::class)],
            'commission.valid_from' => ['required', 'date'],
            'commission.valid_until' => ['nullable', 'date'],
        ], attributes: $this->prefixed('commission', 'suppliers.commission_fields'))['commission'];

        try {
            $add->execute(
                $this->supplier(),
                ProductType::from($data['product_type']),
                Percentage::fromString((string) $data['rate']),
                CommissionBase::from($data['base']),
                CarbonImmutable::parse($data['valid_from']),
                $data['valid_until'] ? CarbonImmutable::parse($data['valid_until']) : null,
                $this->actor(),
            );
        } catch (SupplierRuleViolation $violation) {
            $this->addError('commission.valid_from', $violation->getMessage());

            return;
        }

        $this->reset('commission');
    }

    public function endCommission(int $commissionId, EndCommissionAction $end): void
    {
        Gate::authorize('manage', Supplier::class);
        $commission = $this->supplier()->commissions()->whereKey($commissionId)->first() ?? abort(404);

        try {
            $end->execute($commission, CarbonImmutable::yesterday()->max($commission->valid_from));
        } catch (SupplierRuleViolation $violation) {
            $this->addError('commission.valid_from', $violation->getMessage());
        }
    }

    public function addBankAccount(AddBankAccountAction $add): void
    {
        $data = $this->validate([
            'account.bank_name' => ['required', 'string', 'max:100'],
            'account.account_type' => ['required', Rule::enum(BankAccountType::class)],
            'account.number' => ['required', 'string', 'max:34', 'regex:/^[A-Za-z0-9\s.\-]+$/'],
            'account.holder_name' => ['required', 'string', 'max:255'],
            'account.currency' => ['required', 'string', 'size:3', 'alpha'],
        ], attributes: $this->prefixed('account', 'suppliers.account_fields'))['account'];

        try {
            $add->execute($this->supplier(), $data['bank_name'], BankAccountType::from($data['account_type']), $data['number'], $data['holder_name'], $data['currency'], $this->actor());
        } catch (SupplierRuleViolation $violation) {
            $this->addError('account.number', $violation->getMessage());

            return;
        }

        $this->reset('account');
    }

    public function revealAccount(string $accountUlid, RevealBankAccountAction $reveal): void
    {
        $this->validate(['revealReason' => ['required', 'string', 'max:255']], attributes: ['revealReason' => __('customers.reveal_reason')]);
        $account = $this->supplier()->bankAccounts()->where('ulid', $accountUlid)->first() ?? abort(404);

        try {
            $this->revealed[$accountUlid] = $reveal->execute($account, $this->actor(), $this->revealReason);
        } catch (SupplierRuleViolation $violation) {
            $this->addError('revealReason', $violation->getMessage());
        }
    }

    public function render(): View
    {
        $supplier = $this->supplier()->load(['contacts', 'commissions', 'bankAccounts']);
        $today = CarbonImmutable::today();

        return view('suppliers::livewire.supplier-show', [
            'supplier' => $supplier,
            'standing' => $supplier->standingOn($today, config()->integer('travel.suppliers.rnt_expiry_warning_days')),
            'today' => $today,
            'canManage' => Gate::allows('manage', Supplier::class),
            'canSeeBank' => $this->actor()->can(Permission::FinanceAccess->value),
            'productTypes' => ProductType::cases(),
            'bases' => CommissionBase::cases(),
            'accountTypes' => BankAccountType::cases(),
        ])->title($supplier->trade_name)
            ->layoutData(['heading' => $supplier->trade_name]);
    }

    /** @return array<string, string> */
    private function prefixed(string $prefix, string $translationKey): array
    {
        /** @var array<string, string> $labels */
        $labels = trans($translationKey);

        return collect($labels)->mapWithKeys(static fn(string $label, string $field): array => ["{$prefix}.{$field}" => $label])->all();
    }

    private function supplier(): Supplier
    {
        return Supplier::query()->where('ulid', $this->supplierUlid)->firstOrFail();
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
