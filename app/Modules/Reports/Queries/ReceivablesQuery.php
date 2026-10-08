<?php

declare(strict_types=1);

namespace App\Modules\Reports\Queries;

use App\Modules\Bookings\Contracts\BookingMetrics;
use App\Modules\Bookings\Data\ReceivableBase;
use App\Modules\Payments\Contracts\BookingCollections;
use App\Modules\Reports\Data\Receivable;
use App\Modules\Shared\Contracts\ScopedViewer;
use App\Modules\Shared\ValueObjects\AgingBuckets;
use Brick\Money\Money;
use Carbon\CarbonImmutable;

/**
 * KPI Cartera por edades (adaptado: el saldo vence N días ⚙ antes del primer servicio, no tras emitir factura).
 * Expedientes vigentes con saldo > 0 en la moneda de la agencia: vencida, vence en ≤ N días ⚙ y posterior.
 * Lo que se debe lo aporta Reservas y lo cobrado, Cobros (ADR-0007).
 */
final readonly class ReceivablesQuery
{
    public function __construct(
        private BookingMetrics $bookings,
        private BookingCollections $collections,
    ) {}

    /** @return array{buckets: AgingBuckets, items: list<Receivable>} */
    public function for(ScopedViewer $viewer, CarbonImmutable $today): array
    {
        $currency = config()->string('travel.agency.default_currency');
        $bases = $this->bookings->receivableBases($viewer, $currency, config()->integer('travel.reports.receivables_scan_limit'));
        $collected = $this->collections->netCollected(array_fill_keys(array_map(static fn(ReceivableBase $base): string => $base->bookingUlid, $bases), $currency));
        $soon = $today->addDays(config()->integer('travel.reports.due_soon_days'));
        $buckets = AgingBuckets::zero($currency);
        $items = [];

        foreach ($bases as $base) {
            $balance = $base->total->minus($collected[$base->bookingUlid] ?? Money::zero($currency));
            if (! $balance->isPositive()) {
                continue;
            }

            $due = $base->firstServiceDate?->subDays(config()->integer('travel.payments.balance_due_days_before'));
            $buckets = match (true) {
                $due instanceof CarbonImmutable && $due->lessThan($today) => new AgingBuckets($buckets->overdue->plus($balance), $buckets->dueSoon, $buckets->later, $buckets->count + 1),
                $due instanceof CarbonImmutable && $due->lessThanOrEqualTo($soon) => new AgingBuckets($buckets->overdue, $buckets->dueSoon->plus($balance), $buckets->later, $buckets->count + 1),
                default => new AgingBuckets($buckets->overdue, $buckets->dueSoon, $buckets->later->plus($balance), $buckets->count + 1),
            };
            $items[] = new Receivable($base->bookingUlid, $base->bookingNumber, $base->customerName, $balance, $due);
        }

        usort($items, static fn(Receivable $left, Receivable $right): int => ($left->dueDate?->getTimestamp() ?? PHP_INT_MAX) <=> ($right->dueDate?->getTimestamp() ?? PHP_INT_MAX));

        return ['buckets' => $buckets, 'items' => $items];
    }
}
