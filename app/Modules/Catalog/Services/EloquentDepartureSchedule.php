<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Contracts\DepartureSchedule;
use App\Modules\Catalog\Data\ScheduledDeparture;
use App\Modules\Catalog\Models\CatalogDeparture;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

final class EloquentDepartureSchedule implements DepartureSchedule
{
    public function between(CarbonImmutable $from, CarbonImmutable $to): array
    {
        return $this->map(CatalogDeparture::query()->whereBetween('service_date', [$from->toDateString(), $to->toDateString()]));
    }

    public function find(string $departureUlid): ?ScheduledDeparture
    {
        return $this->map(CatalogDeparture::query()->where('ulid', $departureUlid))[0] ?? null;
    }

    public function findMany(array $departureUlids): array
    {
        return $departureUlids === [] ? [] : $this->map(CatalogDeparture::query()->whereIn('ulid', $departureUlids));
    }

    /**
     * @param  Builder<CatalogDeparture>  $query
     * @return list<ScheduledDeparture>
     */
    private function map(Builder $query): array
    {
        return array_values($query
            ->with('product:id,ulid,name,destination_city,timezone,duration_minutes')
            ->orderBy('service_date')
            ->orderBy('starts_at')
            ->get()
            ->map(static fn(CatalogDeparture $departure): ScheduledDeparture => new ScheduledDeparture(
                $departure->ulid,
                $departure->product->ulid,
                $departure->product->name,
                $departure->product->destination_city,
                $departure->product->timezone,
                $departure->service_date,
                substr($departure->starts_at, 0, 5),
                $departure->product->duration_minutes,
                $departure->capacity,
                $departure->reserved_seats,
                $departure->status,
            ))->all());
    }
}
