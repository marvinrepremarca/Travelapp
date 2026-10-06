<?php

declare(strict_types=1);

namespace App\Modules\Search\Database\Seeders;

use App\Modules\Search\Models\Airport;
use App\Modules\Search\Services\PlaceDirectory;
use App\Modules\Search\Support\PlaceText;
use Illuminate\Database\Seeder;

/** Aeropuertos de referencia (Database/Data/airports.php). Idempotente: actualiza por código IATA. */
final class AirportsSeeder extends Seeder
{
    public function run(PlaceDirectory $places): void
    {
        /** @var list<array{string, string, string, string}> $airports */
        $airports = require __DIR__ . '/../Data/airports.php';
        $rows = [];
        foreach ($airports as $priority => [$iata, $city, $name, $country]) {
            $rows[] = [
                'iata_code' => $iata,
                'city' => $city,
                'name' => $name,
                'country_code' => $country,
                'priority' => $priority,
                'search_text' => PlaceText::normalize(implode(' ', [$iata, $city, $name, $places->countryName($country)])),
            ];
        }

        Airport::query()->upsert($rows, ['iata_code'], ['city', 'name', 'country_code', 'priority', 'search_text']);
    }
}
