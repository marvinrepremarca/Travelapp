<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Services;

use App\Modules\Bookings\Data\CancellationPolicy;
use App\Modules\Bookings\Enums\BookingItemStatus;
use App\Modules\Bookings\Models\BookingItem;
use Brick\Money\Money;
use Carbon\CarbonImmutable;

/**
 * Penalidad de cancelar un servicio hoy: días de anticipación entre la fecha de hoy en la zona de la agencia
 * y la fecha del servicio. Solo los servicios confirmados con política tienen penalidad.
 */
final class CancellationPenaltyCalculator
{
    public function penaltyFor(BookingItem $item, CarbonImmutable $now): ?Money
    {
        if ($item->status !== BookingItemStatus::Confirmed || $item->cancellation_policy === null) {
            return null;
        }

        return CancellationPolicy::fromArray($item->cancellation_policy)->penaltyFor($item->saleAmount(), $this->daysBefore($item, $now));
    }

    public function daysBefore(BookingItem $item, CarbonImmutable $now): int
    {
        // Se comparan fechas de calendario (sin hora ni zona): hoy en la agencia contra la fecha local del servicio.
        $today = CarbonImmutable::parse($now->setTimezone(config()->string('travel.agency.timezone'))->toDateString());

        return (int) $today->diffInDays(CarbonImmutable::parse($item->service_date->toDateString()), false);
    }
}
