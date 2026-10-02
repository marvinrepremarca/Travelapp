<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Contracts\CatalogInventory;
use App\Modules\Catalog\Data\DepartureSlot;
use App\Modules\Catalog\Enums\DepartureStatus;
use App\Modules\Catalog\Enums\SeatHoldStatus;
use App\Modules\Catalog\Exceptions\CatalogRuleViolation;
use App\Modules\Catalog\Models\CatalogDeparture;
use App\Modules\Catalog\Models\CatalogSeatHold;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** Apartados con bloqueo pesimista de la salida: dos ventas simultáneas nunca superan el cupo. */
final class EloquentCatalogInventory implements CatalogInventory
{
    /** HH:MM de la columna time (que llega como HH:MM:SS). */
    private const TIME_LENGTH = 5;

    public function hold(string $departureUlid, int $seats, string $reference, string $idempotencyKey): string
    {
        return DB::transaction(static function () use ($departureUlid, $seats, $reference, $idempotencyKey): string {
            $departure = CatalogDeparture::query()->where('ulid', $departureUlid)->lockForUpdate()->firstOrFail();

            // Se busca después del bloqueo: un reintento concurrente espera y encuentra el apartado ya creado.
            $existing = CatalogSeatHold::query()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing instanceof CatalogSeatHold) {
                return $existing->ulid;
            }

            if ($departure->status !== DepartureStatus::Open) {
                throw CatalogRuleViolation::departureNotOpen();
            }

            if ($seats > $departure->availableSeats()) {
                throw CatalogRuleViolation::soldOut($departure->availableSeats());
            }

            $departure->reserved_seats += $seats;
            $departure->save();

            $hold = new CatalogSeatHold(['departure_id' => $departure->id, 'seats' => $seats, 'reference' => $reference, 'idempotency_key' => $idempotencyKey]);
            $hold->status = SeatHoldStatus::Held;
            $hold->save();

            return $hold->ulid;
        });
    }

    public function departuresOn(string $productUlid, CarbonImmutable $serviceDate): array
    {
        return array_values(CatalogDeparture::query()
            ->whereHas('product', static fn($query) => $query->where('ulid', $productUlid))
            ->where('service_date', $serviceDate->toDateString())
            ->orderBy('starts_at')
            ->get()
            ->map(static fn(CatalogDeparture $departure): DepartureSlot => new DepartureSlot(
                $departure->ulid,
                substr($departure->starts_at, 0, self::TIME_LENGTH),
                $departure->availableSeats(),
                $departure->status === DepartureStatus::Open,
            ))
            ->all());
    }

    public function release(string $holdUlid): void
    {
        DB::transaction(static function () use ($holdUlid): void {
            $hold = CatalogSeatHold::query()->where('ulid', $holdUlid)->lockForUpdate()->firstOrFail();
            if ($hold->status === SeatHoldStatus::Released) {
                return;
            }

            $departure = CatalogDeparture::query()->whereKey($hold->departure_id)->lockForUpdate()->firstOrFail();
            $departure->reserved_seats = max($departure->reserved_seats - $hold->seats, 0);
            $departure->save();

            $hold->status = SeatHoldStatus::Released;
            $hold->released_at = CarbonImmutable::now();
            $hold->save();
        });
    }
}
