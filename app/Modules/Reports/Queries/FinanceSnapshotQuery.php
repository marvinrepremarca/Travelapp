<?php

declare(strict_types=1);

namespace App\Modules\Reports\Queries;

use App\Modules\Finance\Contracts\FinanceMetrics;
use App\Modules\Finance\Data\OpenCashBalance;
use App\Modules\Invoicing\Contracts\InvoicingMetrics;
use App\Modules\Reports\Data\Period;
use App\Modules\Shared\ValueObjects\AgingBuckets;
use Brick\Money\Money;
use Carbon\CarbonImmutable;

/** Foto financiera del tablero: cuentas por pagar y cajas (Contabilidad) y emisión (Facturación), cada una desde su capacidad. */
final readonly class FinanceSnapshotQuery
{
    public function __construct(
        private FinanceMetrics $finance,
        private InvoicingMetrics $invoicing,
    ) {}

    public function payables(CarbonImmutable $today): AgingBuckets
    {
        return $this->finance->payablesAging($today, $today->addDays(config()->integer('travel.reports.due_soon_days')), $this->currency());
    }

    /** @return list<OpenCashBalance> */
    public function openCash(): array
    {
        return $this->finance->openCash();
    }

    /** @return array<string, Money> tipo de documento → total emitido en el período */
    public function invoicing(Period $period): array
    {
        return $this->invoicing->issuedTotals($period->from(), $period->until(), $this->currency());
    }

    private function currency(): string
    {
        return config()->string('travel.agency.default_currency');
    }
}
