<?php

declare(strict_types=1);

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Enums\PayableStatus;
use App\Modules\Finance\Exceptions\FinanceRuleViolation;
use App\Modules\Finance\Models\SupplierPayable;
use App\Modules\Identity\Models\User;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Liquidación: finanzas paga de una vez varias obligaciones pendientes de UN proveedor y en UNA moneda,
 * con el comprobante de la transferencia. Devuelve el total pagado.
 */
final class SettleSupplierPayablesAction
{
    /**
     * @param  list<string>  $payableUlids
     */
    public function execute(User $actor, array $payableUlids, string $paymentReference, CarbonImmutable $now): Money
    {
        return DB::transaction(static function () use ($actor, $payableUlids, $paymentReference, $now): Money {
            $payables = SupplierPayable::query()->whereIn('ulid', $payableUlids)->lockForUpdate()->get();
            if ($payables->isEmpty() || $payables->count() !== count(array_unique($payableUlids)) || $payables->contains(static fn(SupplierPayable $payable): bool => $payable->status !== PayableStatus::Open)) {
                throw FinanceRuleViolation::payablesNotPayable();
            }

            if ($payables->pluck('supplier_id')->unique()->count() > 1 || $payables->pluck('currency')->unique()->count() > 1) {
                throw FinanceRuleViolation::mixedSettlement();
            }

            $total = Money::zero((string) $payables->firstOrFail()->currency);
            $settlementUlid = (string) Str::ulid();
            foreach ($payables as $payable) {
                $payable->status = PayableStatus::Paid;
                $payable->payment_reference = $paymentReference;
                $payable->settlement_ulid = $settlementUlid;
                $payable->paid_by = $actor->id;
                $payable->paid_at = $now;
                $payable->save();
                $total = $total->plus($payable->amount());
            }

            return $total;
        });
    }
}
