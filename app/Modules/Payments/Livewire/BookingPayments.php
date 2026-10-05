<?php

declare(strict_types=1);

namespace App\Modules\Payments\Livewire;

use App\Modules\Bookings\Contracts\BookingAccounts;
use App\Modules\Bookings\Data\BookingAccount;
use App\Modules\Identity\Models\User;
use App\Modules\Payments\Actions\CreatePaymentLinkAction;
use App\Modules\Payments\Actions\PayRefundAction;
use App\Modules\Payments\Actions\RecordPaymentAction;
use App\Modules\Payments\Actions\RequestRefundAction;
use App\Modules\Payments\Actions\ValidateTransferAction;
use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\Refund;
use App\Modules\Payments\Services\PaymentLedger;
use App\Modules\Shared\Enums\Permission;
use App\Modules\Shared\Exceptions\BusinessRuleException;
use App\Modules\Shared\Money\MoneyPresenter;
use App\Modules\Shared\Support\VisibilityGuard;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Estado de cuenta y abonos de un expediente: transferencias, efectivo y links de pago de la pasarela. */
#[Layout('components.layouts.backoffice')]
final class BookingPayments extends Component
{
    #[Locked]
    public string $bookingUlid;

    /** @var array<string, string> */
    public array $form = ['method' => 'bank_transfer', 'amount' => '', 'reference' => '', 'note' => ''];

    /** @var array<string, string> */
    public array $refund = ['amount' => '', 'reason' => ''];

    public string $payoutReference = '';

    public function mount(string $booking): void
    {
        $this->bookingUlid = $booking;
        $this->account();
    }

    public function register(RecordPaymentAction $record, CreatePaymentLinkAction $createLink): void
    {
        $account = $this->account();
        $isTransfer = $this->form['method'] === PaymentMethod::BankTransfer->value;
        $data = $this->validate([
            'form.method' => ['required', Rule::enum(PaymentMethod::class)],
            'form.amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'form.reference' => [$isTransfer ? 'required' : 'nullable', 'string', 'max:100'],
            'form.note' => ['nullable', 'string', 'max:2000'],
        ], attributes: $this->prefixed())['form'];

        $amount = Money::of((string) $data['amount'], $account->saleTotal->getCurrency());
        $method = PaymentMethod::from($data['method']);

        try {
            $method === PaymentMethod::OnlineLink
                ? $createLink->execute($this->actor(), $account, $amount, CarbonImmutable::now())
                : $record->execute($this->actor(), $account, $method, $amount, $data['reference'] ?: null, $data['note'] ?: null, CarbonImmutable::now());
        } catch (BusinessRuleException $violation) {
            $this->addError('form.amount', $violation->getMessage());

            return;
        }

        $this->reset('form');
    }

    public function validateTransfer(string $paymentUlid, bool $approve, ValidateTransferAction $validate): void
    {
        abort_unless($this->actor()->can(Permission::FinanceAccess->value), 403);
        $this->account();
        $payment = Payment::query()->where('booking_ulid', $this->bookingUlid)->where('ulid', $paymentUlid)->first() ?? abort(404);

        try {
            $validate->execute($this->actor(), $payment, $approve, null, CarbonImmutable::now());
        } catch (BusinessRuleException $violation) {
            $this->addError('payments', $violation->getMessage());
        }
    }

    public function requestRefund(RequestRefundAction $request): void
    {
        $account = $this->account();
        $data = $this->validate([
            'refund.amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'refund.reason' => ['required', 'string', 'max:2000'],
        ], attributes: ['refund.amount' => __('payments.refunds.amount'), 'refund.reason' => __('payments.refunds.reason')])['refund'];

        try {
            $request->execute($this->actor(), $account, Money::of((string) $data['amount'], $account->saleTotal->getCurrency()), (string) $data['reason'], CarbonImmutable::now());
        } catch (BusinessRuleException $violation) {
            $this->addError('refund.amount', $violation->getMessage());

            return;
        }

        $this->reset('refund');
    }

    public function payRefund(string $refundUlid, PayRefundAction $pay): void
    {
        abort_unless($this->actor()->can(Permission::FinanceAccess->value), 403);
        $this->account();
        $refund = Refund::query()->where('booking_ulid', $this->bookingUlid)->where('ulid', $refundUlid)->first() ?? abort(404);
        $this->validate(['payoutReference' => ['required', 'string', 'max:100']], attributes: ['payoutReference' => __('payments.refunds.payout_reference')]);

        try {
            $pay->execute($this->actor(), $refund, $this->payoutReference, CarbonImmutable::now());
        } catch (BusinessRuleException $violation) {
            $this->addError('payoutReference', $violation->getMessage());

            return;
        }

        $this->reset('payoutReference');
    }

    public function render(PaymentLedger $ledger, MoneyPresenter $presenter): View
    {
        $account = $this->account();

        return view('payments::livewire.booking-payments', [
            'account' => $account,
            'summary' => $ledger->summary($account, CarbonImmutable::now()),
            'payments' => Payment::query()->where('booking_ulid', $account->ulid)->latest('id')->get(),
            'refunds' => Refund::query()->where('booking_ulid', $account->ulid)->latest('id')->get(),
            'methods' => PaymentMethod::cases(),
            'canValidate' => $this->actor()->can(Permission::FinanceAccess->value),
            'presenter' => $presenter,
            'timezone' => config()->string('travel.agency.timezone'),
        ])->title(__('payments.title', ['number' => $account->number]))
            ->layoutData(['heading' => __('payments.title', ['number' => $account->number])]);
    }

    /** Expediente dentro del alcance del usuario; si no existe o es ajeno, 404. */
    private function account(): BookingAccount
    {
        try {
            $account = once(fn(): BookingAccount => app(BookingAccounts::class)->account($this->bookingUlid));
        } catch (ModelNotFoundException) {
            abort(404);
        }

        abort_unless(VisibilityGuard::allows($this->actor(), $account->ownerId, $account->branchId), 404);

        return $account;
    }

    /** @return array<string, string> */
    private function prefixed(): array
    {
        /** @var array<string, string> $labels */
        $labels = trans('payments.fields');

        return collect($labels)->mapWithKeys(static fn(string $label, string $field): array => ["form.{$field}" => $label])->all();
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
