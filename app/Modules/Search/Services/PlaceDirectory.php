<?php

declare(strict_types=1);

namespace App\Modules\Search\Services;

use App\Modules\Search\Data\PlaceSuggestion;
use App\Modules\Search\Models\Airport;
use App\Modules\Search\Support\PlaceText;
use Illuminate\Database\Eloquent\Builder;
use ResourceBundle;

/**
 * Autocompletar de lugares por nombre natural: aeropuertos y ciudades (tabla `airports`) y países (ICU, en el idioma de la app).
 * Traduce lo que el usuario elige al código que necesitan los proveedores: IATA de 3 letras e ISO 3166-1 alfa-2.
 */
final class PlaceDirectory
{
    private const IATA_LENGTH = 3;

    private const ISO_COUNTRY_PATTERN = '/^[A-Z]{2}$/';

    private const LABEL_CODE_PATTERN = '/\(([A-Za-z]{3})\)\s*$/';

    /** Regiones de ICU que no son países (Unión Europea, Naciones Unidas, códigos privados, etc.). */
    private const NOT_COUNTRIES = ['AC', 'CP', 'DG', 'EA', 'EU', 'EZ', 'IC', 'QO', 'TA', 'UN', 'XA', 'XB', 'ZZ'];

    /** @var array<string, string>|null */
    private ?array $countries = null;

    /** @return list<PlaceSuggestion> */
    public function airports(string $term): array
    {
        if (! $this->isSearchable($term)) {
            return [];
        }

        return array_values($this->matching($term)
            ->limit($this->limit())
            ->get()
            ->map(fn(Airport $airport): PlaceSuggestion => new PlaceSuggestion(
                $airport->iata_code,
                $this->airportLabel($airport),
                $airport->name . ' · ' . $this->countryName($airport->country_code),
            ))->all());
    }

    /**
     * Ciudades con aeropuerto; el valor es el código IATA de su aeropuerto principal (de él salen ciudad y país).
     *
     * @return list<PlaceSuggestion>
     */
    public function cities(string $term): array
    {
        if (! $this->isSearchable($term)) {
            return [];
        }

        $seen = [];
        $suggestions = [];
        foreach ($this->matching($term)->get() as $airport) {
            $key = $airport->city . '|' . $airport->country_code;
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $suggestions[] = new PlaceSuggestion($airport->iata_code, $this->cityLabel($airport), $this->countryName($airport->country_code));
            if (count($suggestions) === $this->limit()) {
                break;
            }
        }

        return $suggestions;
    }

    /** @return list<PlaceSuggestion> */
    public function countries(string $term): array
    {
        if (! $this->isSearchable($term)) {
            return [];
        }

        $needle = PlaceText::normalize($term);
        $suggestions = [];
        foreach ($this->countryNames() as $code => $name) {
            if (str_contains(PlaceText::normalize($name), $needle) || mb_strtoupper(trim($term)) === $code) {
                $suggestions[] = new PlaceSuggestion($code, $name, $code);
            }
        }

        return array_slice($suggestions, 0, $this->limit());
    }

    public function airport(string $iataCode): ?Airport
    {
        return Airport::query()->where('iata_code', mb_strtoupper($iataCode))->first();
    }

    /** Código IATA a partir de lo escrito: el código exacto, el texto de una sugerencia o una única coincidencia. */
    public function airportCode(string $text): ?string
    {
        $text = trim($text);
        if (mb_strlen($text) === self::IATA_LENGTH && $this->airport($text) instanceof Airport) {
            return mb_strtoupper($text);
        }

        // Texto de una sugerencia ya elegida: "Cartagena (CTG)".
        if (preg_match(self::LABEL_CODE_PATTERN, $text, $match) === 1 && $this->airport($match[1]) instanceof Airport) {
            return mb_strtoupper($match[1]);
        }

        $candidates = $this->airports($text);

        return count($candidates) === 1 ? $candidates[0]->value : null;
    }

    /** Código ISO a partir de lo escrito: el código exacto, el nombre del país o una única coincidencia. */
    public function countryCode(string $text): ?string
    {
        $text = trim($text);
        $code = mb_strtoupper($text);
        if (isset($this->countryNames()[$code])) {
            return $code;
        }

        $candidates = $this->countries($text);
        foreach ($candidates as $candidate) {
            if (PlaceText::normalize($candidate->label) === PlaceText::normalize($text)) {
                return $candidate->value;
            }
        }

        return count($candidates) === 1 ? $candidates[0]->value : null;
    }

    public function countryName(string $code): string
    {
        return $this->countryNames()[$code] ?? $code;
    }

    public function airportLabel(Airport $airport): string
    {
        return "{$airport->city} ({$airport->iata_code})";
    }

    public function cityLabel(Airport $airport): string
    {
        return $airport->city . ', ' . $this->countryName($airport->country_code);
    }

    /** @return Builder<Airport> */
    private function matching(string $term): Builder
    {
        $needle = PlaceText::normalize($term);

        return Airport::query()
            ->where(static fn(Builder $query) => $query
                ->where('iata_code', mb_strtoupper(trim($term)))
                ->orWhere('search_text', 'like', '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $needle) . '%'))
            ->orderByRaw('case when iata_code = ? then 0 else 1 end', [mb_strtoupper(trim($term))])
            ->orderBy('priority');
    }

    /** @return array<string, string> código ISO → nombre en el idioma de la aplicación, ordenado por nombre */
    private function countryNames(): array
    {
        if ($this->countries !== null) {
            return $this->countries;
        }

        $countries = [];
        $bundle = ResourceBundle::create(app()->getLocale(), 'ICUDATA-region');
        $regions = $bundle instanceof ResourceBundle ? $bundle->get('Countries') : null;
        if ($regions instanceof ResourceBundle) {
            foreach ($regions as $code => $name) {
                if (is_string($code) && is_string($name) && preg_match(self::ISO_COUNTRY_PATTERN, $code) === 1 && ! in_array($code, self::NOT_COUNTRIES, true)) {
                    $countries[$code] = $name;
                }
            }
        }

        uasort($countries, static fn(string $left, string $right): int => strcmp(PlaceText::normalize($left), PlaceText::normalize($right)));

        return $this->countries = $countries;
    }

    private function isSearchable(string $term): bool
    {
        return mb_strlen(trim($term)) >= config()->integer('travel.search.autocomplete_min_chars');
    }

    private function limit(): int
    {
        return config()->integer('travel.search.autocomplete_limit');
    }
}
