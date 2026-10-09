<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Contracts\FinanceMetrics;
use App\Modules\Finance\Data\OpenCashBalance;
use App\Modules\Finance\Enums\CashMovementType;
use App\Modules\Finance\Enums\CashSessionStatus;
use App\Modules\Finance\Enums\PayableStatus;
use App\Modules\Finance\Models\CashMovement;
use App\Modules\Finance\Models\CashSession;
use App\Modules\Finance\Models\SupplierPayable;
use App\Modules\Shared\ValueObjects\AgingBuckets;
use Brick\Money\Money;
use Carbon\CarbonImmutable;

final class EloquentFinanceMetrics implements FinanceMetrics
{
    private const NO_BRANCH = '—';

    public function payablesAging(CarbonImmutable $today, CarbonImmutable $dueSoonUntil, string $currency): AgingBuckets
    {
        $now = $today->toDateString();
        $soon = $dueSoonUntil->toDateString();
        $row = SupplierPayable::query()
            ->where('status', PayableStatus::Open)
            ->where('currency', $currency)
            ->toBase()
            ->selectRaw('sum(case when due_date < ? then amount_minor else 0 end) as overdue', [$now])
            ->selectRaw('sum(case when due_date >= ? and due_date <= ? then amount_minor else 0 end) as soon', [$now, $soon])
            ->selectRaw('sum(case when due_date > ? then amount_minor else 0 end) as later', [$soon])
            ->selectRaw('count(*) as items')
            ->first();

        return new AgingBuckets(
            Money::ofMinor((int) ($row->overdue ?? 0), $currency),
            Money::ofMinor((int) ($row->soon ?? 0), $currency),
            Money::ofMinor((int) ($row->later ?? 0), $currency),
            (int) ($row->items ?? 0),
        );
    }

    public function openCash(): array
    {
        $sessions = CashSession::query()->where('status', CashSessionStatus::Open)->with('branch:id,name')->get();
        if ($sessions->isEmpty()) {
            return [];
        }

        $totals = CashMovement::query()
            ->whereIn('cash_session_id', $sessions->pluck('id'))
            ->toBase()
            ->selectRaw('cash_session_id, type, sum(amount_minor) as total')
            ->groupBy('cash_session_id', 'type')
            ->get()
            ->groupBy('cash_session_id');

        return array_values($sessions->map(static function (CashSession $session) use ($totals): OpenCashBalance {
            $byType = ($totals->get($session->id) ?? collect())->pluck('total', 'type');
            $expected = $session->opening_amount_minor
                + (int) $byType->get(CashMovementType::Income->value, 0)
                - (int) $byType->get(CashMovementType::Expense->value, 0)
                - (int) $byType->get(CashMovementType::BankDeposit->value, 0);

            return new OpenCashBalance($session->branch->name ?? self::NO_BRANCH, $session->money($expected), $session->opened_at);
        })->all());
    }
}
