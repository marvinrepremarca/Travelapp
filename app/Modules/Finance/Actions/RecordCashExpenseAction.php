<?php

declare(strict_types=1);

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Enums\CashMovementType;
use App\Modules\Finance\Models\CashMovement;
use App\Modules\Finance\Models\CashSession;
use App\Modules\Finance\Services\CashDesk;
use App\Modules\Identity\Models\User;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;

/** Registra una salida de efectivo (gasto menor o consignación al banco) con su descripción. */
final readonly class RecordCashExpenseAction
{
    public function __construct(private CashDesk $desk) {}

    public function execute(User $actor, CashSession $session, Money $amount, string $description, CashMovementType $type = CashMovementType::Expense): CashMovement
    {
        if (! in_array($type, CashMovementType::outflows(), true)) {
            throw new \InvalidArgumentException('Solo se registran salidas de efectivo.');
        }

        return DB::transaction(function () use ($actor, $session, $amount, $description, $type): CashMovement {
            $locked = CashSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();

            return $this->desk->record($locked, $actor, $type, $amount, $description);
        });
    }
}
