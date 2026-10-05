<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Services;

use App\Modules\Bookings\Contracts\BookingProfitLines;
use App\Modules\Bookings\Data\ProfitLine;
use App\Modules\Bookings\Enums\BookingItemStatus;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Shared\Contracts\ScopedViewer;
use App\Modules\Shared\Enums\ProductType;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/** Una sola consulta agrupada: los servicios cerrados (cancelados o rechazados) solo aportan penalidades. */
final class EloquentBookingProfitLines implements BookingProfitLines
{
    private const GROUP_COLUMNS = [
        'bookings.ulid', 'bookings.number', 'bookings.title', 'bookings.owner_id', 'bookings.branch_id',
        'bookings.sale_currency', 'bookings.created_at', 'booking_items.supplier_id', 'booking_items.product_type',
    ];

    public function soldBetween(ScopedViewer $viewer, CarbonImmutable $from, CarbonImmutable $until, ?int $branchId, ?int $ownerId): array
    {
        $closed = array_values(array_map(
            static fn(BookingItemStatus $status): string => $status->value,
            array_filter(BookingItemStatus::cases(), static fn(BookingItemStatus $status): bool => $status->isClosed()),
        ));
        $placeholders = implode(',', array_fill(0, count($closed), '?'));

        $rows = Booking::query()
            ->visibleTo($viewer)
            ->join('booking_items', 'booking_items.booking_id', '=', 'bookings.id')
            ->where('bookings.created_at', '>=', $from)
            ->where('bookings.created_at', '<', $until)
            ->when($branchId !== null, static fn(Builder $query) => $query->where('bookings.branch_id', $branchId))
            ->when($ownerId !== null, static fn(Builder $query) => $query->where('bookings.owner_id', $ownerId))
            ->toBase()
            ->select(self::GROUP_COLUMNS)
            ->selectRaw("sum(case when booking_items.status in ({$placeholders}) then 0 else booking_items.sale_amount_minor end) as sale_minor", $closed)
            ->selectRaw("sum(case when booking_items.status in ({$placeholders}) then 0 else booking_items.sale_amount_minor - booking_items.margin_amount_minor end) as cost_minor", $closed)
            ->selectRaw('sum(coalesce(booking_items.penalty_amount_minor, 0)) as penalties_minor')
            ->groupBy(self::GROUP_COLUMNS)
            ->orderBy('bookings.created_at')
            ->orderBy('bookings.ulid')
            ->get();

        return array_values($rows->map(static function (object $row): ProfitLine {
            $currency = (string) $row->sale_currency;

            return new ProfitLine(
                bookingUlid: (string) $row->ulid,
                bookingNumber: (string) $row->number,
                bookingTitle: (string) $row->title,
                ownerId: (int) $row->owner_id,
                branchId: $row->branch_id === null ? null : (int) $row->branch_id,
                soldAt: CarbonImmutable::parse((string) $row->created_at),
                supplierId: $row->supplier_id === null ? null : (int) $row->supplier_id,
                productType: ProductType::from((string) $row->product_type),
                sale: Money::ofMinor((int) $row->sale_minor, $currency),
                cost: Money::ofMinor((int) $row->cost_minor, $currency),
                penalties: Money::ofMinor((int) $row->penalties_minor, $currency),
            );
        })->all());
    }
}
