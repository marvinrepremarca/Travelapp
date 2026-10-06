<?php

declare(strict_types=1);

namespace App\Modules\Payments\Services;

use App\Modules\Bookings\Contracts\BookingAccounts;
use App\Modules\Payments\Contracts\UpcomingBalances;
use App\Modules\Payments\Data\BalanceDue;
use Carbon\CarbonImmutable;

/** Proceso programado diario: pocos expedientes por fecha, así que se usa el estado de cuenta de cada uno. */
final readonly class LedgerUpcomingBalances implements UpcomingBalances
{
    public function __construct(
        private BookingAccounts $accounts,
        private PaymentLedger $ledger,
    ) {}

    public function dueOn(CarbonImmutable $dueDate): array
    {
        $firstService = $dueDate->addDays(config()->integer('travel.payments.balance_due_days_before'));
        $due = [];
        foreach ($this->accounts->startingOn($firstService) as $bookingUlid) {
            $account = $this->accounts->account($bookingUlid);
            $summary = $this->ledger->summary($account, CarbonImmutable::now());
            if ($summary->balance->isPositive()) {
                $due[] = new BalanceDue($account->ulid, $account->number, $account->customerId, $account->ownerId, $account->branchId, $summary->balance, $dueDate);
            }
        }

        return $due;
    }
}
