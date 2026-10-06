<?php

declare(strict_types=1);

namespace App\Modules\Reports\Queries;

use App\Modules\Bookings\Enums\BookingStatus;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Bookings\Models\BookingItem;
use App\Modules\Payments\Contracts\BookingCollections;
use App\Modules\Reports\Data\AgingBuckets;
use App\Modules\Reports\Data\Receivable;
use App\Modules\Shared\Contracts\ScopedViewer;
use Brick\Money\Money;
use Carbon\CarbonImmutable;

/**
 * KPI Cartera por edades (adaptado: el saldo vence N días ⚙ antes del primer servicio, no tras emitir factura).
 * Expedientes vigentes con saldo > 0 en la moneda de la agencia: vencida, vence en ≤ N días ⚙ y posterior.
 */
final readonly class ReceivablesQuery
{
    public function __construct(private BookingCollections $collections) {}

    /** @return array{buckets: AgingBuckets, items: list<Receivable>} */
    public function for(ScopedViewer $viewer, CarbonImmutable $today): array
    {
        $currency = config()->string('travel.agency.default_currency');
        $bookings = Booking::query()
            ->visibleTo($viewer)
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::InProgress, BookingStatus::NeedsAttention])
            ->where('sale_currency', $currency)
            ->with(['customer:id,display_name', 'items'])
            ->limit(config()->integer('travel.reports.receivables_scan_limit'))
            ->get();

        $collected = $this->collections->netCollected($bookings->mapWithKeys(static fn(Booking $booking): array => [$booking->ulid => $booking->sale_currency])->all());
        $soon = $today->addDays(config()->integer('travel.reports.due_soon_days'));
        $buckets = AgingBuckets::zero($currency);
        $items = [];

        foreach ($bookings as $booking) {
            $total = $booking->saleTotal()->plus($booking->items->reduce(
                static fn(Money $carry, BookingItem $item): Money => $item->penaltyAmount() instanceof Money ? $carry->plus($item->penaltyAmount()) : $carry,
                Money::zero($currency),
            ));
            $balance = $total->minus($collected[$booking->ulid] ?? Money::zero($currency));
            if (! $balance->isPositive()) {
                continue;
            }

            $firstService = $booking->items->reject(static fn(BookingItem $item): bool => $item->status->isClosed())->min('service_date');
            $due = $firstService instanceof CarbonImmutable ? $firstService->subDays(config()->integer('travel.payments.balance_due_days_before')) : null;
            $buckets = match (true) {
                $due instanceof CarbonImmutable && $due->lessThan($today) => new AgingBuckets($buckets->overdue->plus($balance), $buckets->dueSoon, $buckets->later, $buckets->count + 1),
                $due instanceof CarbonImmutable && $due->lessThanOrEqualTo($soon) => new AgingBuckets($buckets->overdue, $buckets->dueSoon->plus($balance), $buckets->later, $buckets->count + 1),
                default => new AgingBuckets($buckets->overdue, $buckets->dueSoon, $buckets->later->plus($balance), $buckets->count + 1),
            };
            $items[] = new Receivable($booking->ulid, (string) $booking->number, $booking->customer->display_name, $balance, $due);
        }

        usort($items, static fn(Receivable $left, Receivable $right): int => ($left->dueDate?->getTimestamp() ?? PHP_INT_MAX) <=> ($right->dueDate?->getTimestamp() ?? PHP_INT_MAX));

        return ['buckets' => $buckets, 'items' => $items];
    }
}
