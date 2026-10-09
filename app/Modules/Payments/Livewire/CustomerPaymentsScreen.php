<?php

declare(strict_types=1);

namespace App\Modules\Payments\Livewire;

use App\Modules\Customers\Models\Customer;
use App\Modules\Identity\Models\User;
use App\Modules\Payments\Actions\RecordCustomerPaymentAction;
use App\Modules\Payments\Actions\ValidateTransferAction;
use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Payments\Models\Payment;
use App\Modules\Shared\Enums\Permission;
use App\Modules\Shared\Exceptions\BusinessRuleException;
use App\Modules\Shared\Money\MoneyPresenter;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/** Abonos de clientes sin expediente (ADR-0007): anticipos y ventas externas; Cobros funciona sin Reservas. */
#[Layout('components.layouts.backoffice')]
final class CustomerPaymentsScreen extends Component
{
    use WithPagination;

    public string $customerUlid = '';

    public string $customerSearch = '';

    /** @var array{method: string, amount: string, concept: string, reference: string} */
    public array $form = ['method' => 'cash', 'amount' => '', 'concept' => '', 'reference' => ''];

    public function chooseCustomer(string $ulid): void
    {
        $this->customerUlid = $ulid;
        $this->customerSearch = '';
    }

    public function register(RecordCustomerPaymentAction $record, MoneyPresenter $presenter): void
    {
        $isTransfer = $this->form['method'] === PaymentMethod::BankTransfer->value;
        $validated = $this->validate([
            'customerUlid' => ['required', 'string'],
            'form.method' => ['required', Rule::enum(PaymentMethod::class)->only($this->methods())],
            'form.amount' => ['required', 'numeric', 'gt:0'],
            'form.concept' => ['required', 'string', 'max:255'],
            'form.reference' => [$isTransfer ? 'required' : 'nullable', 'string', 'max:100'],
        ], attributes: Arr::dot(trans('payments.customer_payments.fields')));

        $customer = $this->visibleCustomers()->where('ulid', $validated['customerUlid'])->first();
        if (! $customer instanceof Customer) {
            $this->addError('customerUlid', __('payments.customer_payments.customer_not_found'));

            return;
        }

        try {
            $payment = $record->execute(
                $this->actor(),
                $customer,
                PaymentMethod::from($validated['form']['method']),
                Money::of($validated['form']['amount'], config()->string('travel.agency.default_currency')),
                $validated['form']['concept'],
                $validated['form']['reference'] ?: null,
                CarbonImmutable::now(),
            );
        } catch (BusinessRuleException $violation) {
            $this->addError('form.amount', $violation->getMessage());

            return;
        }

        session()->flash('status', __('payments.customer_payments.recorded', ['amount' => $presenter->format($payment->amount()), 'customer' => $customer->display_name]));
        $this->reset('form', 'customerUlid');
    }

    public function validateTransfer(string $paymentUlid, bool $approve, ValidateTransferAction $validate): void
    {
        abort_unless($this->actor()->can(Permission::FinanceAccess->value), 403);
        $payment = $this->visiblePayments()->where('ulid', $paymentUlid)->first() ?? abort(404);

        try {
            $validate->execute($this->actor(), $payment, $approve, null, CarbonImmutable::now());
        } catch (BusinessRuleException $violation) {
            $this->addError('payments', $violation->getMessage());
        }
    }

    public function render(MoneyPresenter $presenter): View
    {
        $selected = $this->customerUlid === '' ? null : $this->visibleCustomers()->where('ulid', $this->customerUlid)->first(['id', 'ulid', 'display_name']);
        $matches = $this->customerSearch === '' ? collect() : $this->visibleCustomers()
            ->where('display_name', 'like', '%' . $this->customerSearch . '%')
            ->orderBy('display_name')
            ->limit(config()->integer('travel.payments.per_page'))
            ->get(['id', 'ulid', 'display_name']);
        $title = __('payments.customer_payments.title');

        return view('payments::livewire.customer-payments', [
            'selected' => $selected,
            'matches' => $matches,
            'payments' => $this->visiblePayments()->with('customer:id,display_name')->latest('id')->paginate(config()->integer('travel.payments.per_page')),
            'methods' => $this->methods(),
            'canValidate' => $this->actor()->can(Permission::FinanceAccess->value),
            'currency' => config()->string('travel.agency.default_currency'),
            'presenter' => $presenter,
        ])->title($title)->layoutData(['heading' => $title]);
    }

    /**
     * Sin expediente no hay link de pago: solo efectivo y transferencia.
     *
     * @return list<PaymentMethod>
     */
    private function methods(): array
    {
        return [PaymentMethod::Cash, PaymentMethod::BankTransfer];
    }

    /** @return Builder<Payment> */
    private function visiblePayments(): Builder
    {
        return Payment::query()->visibleTo($this->actor())->whereNull('booking_ulid')->whereNotNull('customer_id');
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
