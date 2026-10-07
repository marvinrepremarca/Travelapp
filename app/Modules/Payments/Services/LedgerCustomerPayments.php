<?php

declare(strict_types=1);

namespace App\Modules\Payments\Services;

use App\Modules\Bookings\Contracts\BookingAccounts;
use App\Modules\Identity\Models\User;
use App\Modules\Payments\Actions\CreatePaymentLinkAction;
use App\Modules\Payments\Contracts\CustomerPayments;
use App\Modules\Payments\Data\BalanceSummary;
use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Models\Payment;
use Carbon\CarbonImmutable;

/** El link lo crea el sistema a nombre del asesor responsable del expediente (queda en su estado de cuenta). */
final readonly class LedgerCustomerPayments implements CustomerPayments
{
    public function __construct(
        private BookingAccounts $accounts,
        private PaymentLedger $ledger,
        private CreatePaymentLinkAction $links,
    ) {}

    public function statement(string $bookingUlid): BalanceSummary
    {
        return $this->ledger->summary($this->accounts->account($bookingUlid), CarbonImmutable::now());
    }

    public function payBalanceUrl(string $bookingUlid): ?string
    {
        $now = CarbonImmutable::now();
        $open = Payment::query()
            ->where('booking_ulid', $bookingUlid)
            ->where('method', PaymentMethod::OnlineLink)
            ->where('status', PaymentStatus::Pending)
            ->where('link_expires_at', '>', $now)
            ->latest('id')
            ->first(['link_url']);
        if ($open instanceof Payment && $open->link_url !== null) {
            return $open->link_url;
        }

        $account = $this->accounts->account($bookingUlid);
        $collectable = $this->ledger->summary($account, $now)->collectable();
        if (! $collectable->isPositive()) {
            return null;
        }

        return $this->links->execute(User::query()->findOrFail($account->ownerId), $account, $collectable, $now)->link_url;
    }
}
