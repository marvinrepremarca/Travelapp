<?php

declare(strict_types=1);

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Data\ManualPayableData;
use App\Modules\Finance\Enums\PayableSource;
use App\Modules\Finance\Enums\PayableStatus;
use App\Modules\Finance\Exceptions\FinanceRuleViolation;
use App\Modules\Finance\Models\SupplierPayable;
use App\Modules\Identity\Models\User;

/** Registra a mano una cuenta por pagar a un proveedor, sin expediente; se liquida igual que las demás. */
final readonly class RegisterManualPayableAction
{
    public function execute(User $actor, ManualPayableData $data): SupplierPayable
    {
        if (! $data->amount->isPositive()) {
            throw FinanceRuleViolation::payableAmountInvalid();
        }

        $payable = new SupplierPayable([
            'supplier_id' => $data->supplierId,
            'owner_id' => $actor->id,
            'branch_id' => $actor->branch_id,
            'description' => $data->description,
            'amount_minor' => $data->amount->getMinorAmount()->toInt(),
            'currency' => $data->amount->getCurrency()->getCurrencyCode(),
            'due_date' => $data->dueDate->toDateString(),
        ]);
        $payable->source = PayableSource::Manual;
        $payable->status = PayableStatus::Open;
        $payable->save();

        return $payable;
    }
}
