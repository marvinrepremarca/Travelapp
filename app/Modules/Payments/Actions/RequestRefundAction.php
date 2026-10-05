<?php

declare(strict_types=1);

namespace App\Modules\Payments\Actions;

use App\Modules\Bookings\Data\BookingAccount;
use App\Modules\Identity\Models\User;
use App\Modules\Payments\Enums\RefundStatus;
use App\Modules\Payments\Exceptions\PaymentRuleViolation;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\Refund;
use App\Modules\Payments\Services\PaymentLedger;
use App\Modules\Shared\Money\MoneyPresenter;
use App\Modules\Workflow\Contracts\Approvals;
use App\Modules\Workflow\Data\ApprovalRequestData;
use App\Modules\Workflow\Enums\ApprovalType;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * El asesor solicita devolver dinero al cliente: como máximo lo pagado de más (descontando servicios vigentes,
 * penalidades y otros reembolsos en curso). Queda pendiente de la aprobación de finanzas (Workflow).
 */
final readonly class RequestRefundAction
{
    public function __construct(
        private PaymentLedger $ledger,
        private Approvals $approvals,
        private MoneyPresenter $presenter,
    ) {}

    public function execute(User $actor, BookingAccount $account, Money $amount, string $reason, CarbonImmutable $now): Refund
    {
        return DB::transaction(function () use ($actor, $account, $amount, $reason, $now): Refund {
            Payment::query()->where('booking_ulid', $account->ulid)->lockForUpdate()->get(['id']);
            $refundable = $this->ledger->summary($account, $now)->refundable();
            if ($amount->isGreaterThan($refundable)) {
                throw PaymentRuleViolation::exceedsRefundable($this->presenter->format($refundable));
            }

            $refund = new Refund([
                'booking_ulid' => $account->ulid,
                'amount_minor' => $amount->getMinorAmount()->toInt(),
                'currency' => $amount->getCurrency()->getCurrencyCode(),
                'reason' => $reason,
            ]);
            $refund->owner_id = $account->ownerId;
            $refund->branch_id = $account->branchId;
            $refund->status = RefundStatus::Requested;
            $refund->requested_by = $actor->id;
            $refund->save();

            $approval = $this->approvals->request(new ApprovalRequestData(
                type: ApprovalType::Refund,
                subject: $refund,
                summary: __('payments.refunds.approval_summary', ['amount' => $this->presenter->format($amount), 'number' => $account->number]),
                justification: $reason,
                context: ['amount' => (string) $amount->getAmount(), 'currency' => $amount->getCurrency()->getCurrencyCode(), 'booking' => $account->number],
            ), $actor);

            $refund->approval_ulid = $approval->ulid;
            $refund->save();

            return $refund;
        });
    }
}
