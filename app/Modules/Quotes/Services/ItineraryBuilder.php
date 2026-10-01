<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Services;

use App\Modules\Shared\Enums\ProductType;
use Carbon\CarbonImmutable;

/**
 * Arma el itinerario día a día de una opción: servicios agrupados por fecha local del servicio,
 * con el día de salida de los que tienen noches (hoteles, autos).
 */
final class ItineraryBuilder
{
    /**
     * @param  iterable<array{description: string, product_type: string, service_date: string, nights: int, ...}>  $items
     * @return list<array{date: CarbonImmutable, day: int, entries: list<array{description: string, type: ProductType, nights: int, ends_on: CarbonImmutable|null}>}>
     */
    public function build(iterable $items): array
    {
        $byDate = [];
        foreach ($items as $item) {
            $date = CarbonImmutable::parse($item['service_date']);
            $byDate[$date->toDateString()][] = [
                'description' => $item['description'],
                'type' => ProductType::from($item['product_type']),
                'nights' => $item['nights'],
                'ends_on' => $item['nights'] > 0 ? $date->addDays($item['nights']) : null,
            ];
        }

        ksort($byDate);
        $first = array_key_first($byDate);
        $days = [];
        foreach ($byDate as $date => $entries) {
            $day = CarbonImmutable::parse($date);
            $days[] = ['date' => $day, 'day' => (int) CarbonImmutable::parse((string) $first)->diffInDays($day) + 1, 'entries' => $entries];
        }

        return $days;
    }
}
