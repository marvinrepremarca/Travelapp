<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Services;

use App\Modules\Invoicing\Contracts\InvoicingMetrics;
use App\Modules\Invoicing\Enums\InvoiceType;
use Brick\Money\Money;
use Carbon\CarbonImmutable;

/** Facturación apagada: nada emitido. */
final class NullInvoicingMetrics implements InvoicingMetrics
{
    public function issuedTotals(CarbonImmutable $from, CarbonImmutable $until, string $currency): array
    {
        $result = [];
        foreach (InvoiceType::cases() as $type) {
            $result[$type->value] = Money::zero($currency);
        }

        return $result;
    }
}
