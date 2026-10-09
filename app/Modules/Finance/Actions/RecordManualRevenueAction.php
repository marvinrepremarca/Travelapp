<?php

declare(strict_types=1);

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Data\ManualRevenueData;
use App\Modules\Finance\Enums\RevenueEntryType;
use App\Modules\Finance\Enums\RevenueSource;
use App\Modules\Finance\Exceptions\FinanceRuleViolation;
use App\Modules\Finance\Models\RevenueEntry;
use App\Modules\Identity\Models\User;
use Carbon\CarbonImmutable;

/** Registra a mano una venta que no pasó por el sistema; queda a nombre y en la sucursal de quien la registra. */
final readonly class RecordManualRevenueAction
{
    public function execute(User $actor, ManualRevenueData $data, CarbonImmutable $today): RevenueEntry
    {
        if (! $data->amount->isPositive()) {
            throw FinanceRuleViolation::revenueAmountInvalid();
        }

        if ($data->recognizedOn->greaterThan($today)) {
            throw FinanceRuleViolation::revenueDateInFuture();
        }

        $entry = new RevenueEntry([
            'description' => $data->description,
            'customer_name' => $data->customerName,
            'amount_minor' => $data->amount->getMinorAmount()->toInt(),
            'currency' => $data->amount->getCurrency()->getCurrencyCode(),
            'recognized_on' => $data->recognizedOn->toDateString(),
            'owner_id' => $actor->id,
            'branch_id' => $actor->branch_id,
            'created_by' => $actor->id,
        ]);
        $entry->source = RevenueSource::Manual;
        $entry->entry_type = RevenueEntryType::Recognition;
        $entry->save();

        return $entry;
    }
}
