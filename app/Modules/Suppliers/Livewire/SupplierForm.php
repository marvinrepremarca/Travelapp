<?php

declare(strict_types=1);

namespace App\Modules\Suppliers\Livewire;

use App\Modules\Suppliers\Actions\SaveSupplierAction;
use App\Modules\Suppliers\Data\SupplierData;
use App\Modules\Suppliers\Enums\PaymentTerms;
use App\Modules\Suppliers\Exceptions\SupplierRuleViolation;
use App\Modules\Suppliers\Models\Supplier;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.backoffice')]
final class SupplierForm extends Component
{
    #[Locked]
    public ?string $supplierUlid = null;

    public string $legal_name = '';

    public string $trade_name = '';

    public string $tax_id = '';

    public string $country = 'CO';

    public bool $is_tourism_provider = true;

    public string $rnt_number = '';

    public string $rnt_expires_on = '';

    public string $email = '';

    public string $phone = '';

    public string $website = '';

    public string $payment_terms = 'credit';

    public string $payment_days = '';

    public string $payment_currency = 'COP';

    public string $notes = '';

    public function mount(?Supplier $supplier = null): void
    {
        Gate::authorize('manage', Supplier::class);

        if (! $supplier?->exists) {
            return;
        }

        $this->supplierUlid = $supplier->ulid;
        $this->fill([
            'legal_name' => $supplier->legal_name,
            'trade_name' => $supplier->trade_name,
            'tax_id' => $supplier->tax_id,
            'country' => $supplier->country,
            'is_tourism_provider' => $supplier->is_tourism_provider,
            'rnt_number' => (string) $supplier->rnt_number,
            'rnt_expires_on' => (string) $supplier->rnt_expires_on?->toDateString(),
            'email' => (string) $supplier->email,
            'phone' => (string) $supplier->phone,
            'website' => (string) $supplier->website,
            'payment_terms' => $supplier->payment_terms->value,
            'payment_days' => (string) $supplier->payment_days,
            'payment_currency' => $supplier->payment_currency,
            'notes' => (string) $supplier->notes,
        ]);
    }

    public function save(SaveSupplierAction $save): void
    {
        Gate::authorize('manage', Supplier::class);
        $supplier = $this->supplierUlid === null ? null : Supplier::query()->where('ulid', $this->supplierUlid)->firstOrFail();

        $validated = $this->validate([
            'legal_name' => ['required', 'string', 'max:255'],
            'trade_name' => ['required', 'string', 'max:255'],
            'tax_id' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9.\-\s]+$/',
                Rule::unique('suppliers', 'tax_id')->where('country', mb_strtoupper($this->country))->ignore($this->supplierUlid, 'ulid')],
            'country' => ['required', 'string', 'size:2', 'alpha'],
            'is_tourism_provider' => ['boolean'],
            'rnt_number' => ['nullable', 'alpha_num', 'max:20'],
            'rnt_expires_on' => ['nullable', 'date'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'website' => ['nullable', 'url:https', 'max:255'],
            'payment_terms' => ['required', Rule::enum(PaymentTerms::class)],
            'payment_days' => ['required', 'integer', 'min:0', 'max:' . config()->integer('travel.suppliers.max_payment_days')],
            'payment_currency' => ['required', 'string', 'size:3', 'alpha'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], attributes: $this->attributes());

        try {
            $saved = $save->execute(new SupplierData(
                legalName: $validated['legal_name'],
                tradeName: $validated['trade_name'],
                taxId: $validated['tax_id'],
                country: $validated['country'],
                isTourismProvider: (bool) $validated['is_tourism_provider'],
                paymentTerms: PaymentTerms::from($validated['payment_terms']),
                paymentDays: (int) $validated['payment_days'],
                paymentCurrency: $validated['payment_currency'],
                rntNumber: $validated['rnt_number'] ?: null,
                rntExpiresOn: $validated['rnt_expires_on'] ? CarbonImmutable::parse($validated['rnt_expires_on']) : null,
                email: $validated['email'] ?: null,
                phone: $validated['phone'] ?: null,
                website: $validated['website'] ?: null,
                notes: $validated['notes'] ?: null,
            ), $supplier);
        } catch (SupplierRuleViolation $violation) {
            $this->addError('rnt_number', $violation->getMessage());

            return;
        }

        session()->flash('status', __('suppliers.saved', ['name' => $saved->trade_name]));
        $this->redirectRoute('suppliers.show', $saved, navigate: true);
    }

    public function render(): View
    {
        $title = $this->supplierUlid === null ? __('suppliers.create') : __('suppliers.edit');

        return view('suppliers::livewire.supplier-form', [
            'terms' => PaymentTerms::cases(),
        ])->title($title)->layoutData(['heading' => $title]);
    }

    /** @return array<string, string> */
    private function attributes(): array
    {
        /** @var array<string, string> */
        return trans('suppliers.fields');
    }
}
