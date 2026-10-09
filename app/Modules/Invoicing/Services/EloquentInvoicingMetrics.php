<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Services;

use App\Modules\Invoicing\Contracts\InvoicingMetrics;
use App\Modules\Invoicing\Enums\InvoiceType;
use App\Modules\Invoicing\Models\Invoice;
use Brick\Money\Money;
use Carbon\CarbonImmutable;

final class EloquentInvoicingMetrics implements InvoicingMetrics
{
    public function issuedTotals(CarbonImmutable $from, CarbonImmutable $until, string $currency): array
    {
        $totals = Invoice::query()
            ->where('currency', $currency)
            ->where('issued_at', '>=', $from)
            ->where('issued_at', '<', $until)
            ->toBase()
            ->selectRaw('type, sum(total_minor) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $result = [];
        foreach (InvoiceType::cases() as $type) {
            $result[$type->value] = Money::ofMinor((int) $totals->get($type->value, 0), $currency);
        }

        return $result;
    }
}
