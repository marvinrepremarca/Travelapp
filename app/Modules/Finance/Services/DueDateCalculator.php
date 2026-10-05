<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Modules\Suppliers\Data\SupplierPaymentTerms;
use App\Modules\Suppliers\Enums\PaymentTerms;
use Carbon\CarbonImmutable;

/** Vencimiento según las condiciones del proveedor: prepago N días ⚙ antes del servicio; crédito, N días después. */
final class DueDateCalculator
{
    public function dueDate(SupplierPaymentTerms $terms, CarbonImmutable $serviceDate): CarbonImmutable
    {
        return $terms->terms === PaymentTerms::Prepaid
            ? $serviceDate->subDays(config()->integer('travel.finance.prepaid_days_before_service'))
            : $serviceDate->addDays($terms->days);
    }
}
