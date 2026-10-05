<?php

declare(strict_types=1);

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Enums\CashSessionStatus;
use App\Modules\Finance\Exceptions\FinanceRuleViolation;
use App\Modules\Finance\Models\CashSession;
use App\Modules\Identity\Models\User;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;

/** Abre la caja de la sucursal con la base contada. Una sola caja abierta por sucursal. */
final class OpenCashSessionAction
{
    public function execute(User $actor, int $branchId, Money $opening, CarbonImmutable $now): CashSession
    {
        $session = new CashSession([
            'branch_id' => $branchId,
            'currency' => $opening->getCurrency()->getCurrencyCode(),
            'opening_amount_minor' => $opening->getMinorAmount()->toInt(),
        ]);
        $session->open_branch_key = $branchId;
        $session->status = CashSessionStatus::Open;
        $session->opened_by = $actor->id;
        $session->opened_at = $now;

        try {
            $session->save();
        } catch (UniqueConstraintViolationException) {
            throw FinanceRuleViolation::cashSessionAlreadyOpen();
        }

        return $session;
    }
}
