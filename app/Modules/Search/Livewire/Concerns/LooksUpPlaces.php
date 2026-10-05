<?php

declare(strict_types=1);

namespace App\Modules\Search\Livewire\Concerns;

use App\Modules\Search\Data\PlaceSuggestion;
use App\Modules\Search\Enums\PlaceKind;
use App\Modules\Search\Models\Airport;
use App\Modules\Search\Services\PlaceDirectory;

/**
 * Campos de lugar con autocompletar: el usuario escribe el nombre natural en `$lookup[campo]`
 * y el código que usan los proveedores (IATA / ISO) queda en `$criteria[campo]`.
 *
 * @property array<string, string> $criteria
 */
trait LooksUpPlaces
{
    /** @var array<string, string> texto visible de cada campo de lugar */
    public array $lookup = [];

    /** Campo que está escribiendo el usuario: solo él consulta sugerencias. */
    public string $activeLookup = '';

    /** @return array<string, PlaceKind> */
    abstract protected function placeFields(): array;

    public function updatedLookup(mixed $value, string $field): void
    {
        $kind = $this->placeFields()[$field] ?? null;
        if (! $kind instanceof PlaceKind) {
            return;
        }

        $this->activeLookup = $field;
        // Escribir invalida el código elegido; una ciudad sin aeropuerto se acepta tal como se escribe.
        $this->criteria[$field] = $kind === PlaceKind::City ? trim((string) $value) : '';
    }

    public function choose(string $field, string $value, PlaceDirectory $places): void
    {
        $kind = $this->placeFields()[$field] ?? null;
        match ($kind) {
            PlaceKind::Airport => $this->chooseAirport($field, $places->airport($value), $places),
            PlaceKind::City => $this->chooseCity($field, $places->airport($value), $places),
            PlaceKind::Country => $this->chooseCountry($field, mb_strtoupper($value), $places),
            null => null,
        };

        $this->activeLookup = '';
    }

    /** Lo escrito sin elegir de la lista se traduce si no es ambiguo (código exacto, nombre exacto o única coincidencia). */
    protected function resolvePlaces(PlaceDirectory $places): void
    {
        foreach ($this->placeFields() as $field => $kind) {
            $typed = $this->lookup[$field] ?? '';
            if (($this->criteria[$field] ?? '') !== '' || $typed === '' || $kind === PlaceKind::City) {
                continue;
            }

            $this->criteria[$field] = (string) ($kind === PlaceKind::Airport ? $places->airportCode($typed) : $places->countryCode($typed));
        }

        $this->activeLookup = '';
    }

    /** @return array<string, string> mensaje "elige de la lista" para lo escrito que no se pudo traducir */
    protected function placeMessages(): array
    {
        $messages = [];
        foreach (array_keys($this->placeFields()) as $field) {
            if (($this->lookup[$field] ?? '') !== '') {
                $messages["criteria.{$field}.required"] = __('search.places.choose_from_list');
            }
        }

        return $messages;
    }

    /** @return array<string, list<PlaceSuggestion>> */
    protected function placeSuggestions(PlaceDirectory $places): array
    {
        $suggestions = array_fill_keys(array_keys($this->placeFields()), []);
        $kind = $this->placeFields()[$this->activeLookup] ?? null;
        $term = $this->lookup[$this->activeLookup] ?? '';

        if ($kind instanceof PlaceKind) {
            $suggestions[$this->activeLookup] = match ($kind) {
                PlaceKind::Airport => $places->airports($term),
                PlaceKind::City => $places->cities($term),
                PlaceKind::Country => $places->countries($term),
            };
        }

        return $suggestions;
    }

    private function chooseAirport(string $field, ?Airport $airport, PlaceDirectory $places): void
    {
        if ($airport instanceof Airport) {
            $this->criteria[$field] = $airport->iata_code;
            $this->lookup[$field] = $places->airportLabel($airport);
            $this->resetErrorBag("criteria.{$field}");
        }
    }

    /** Elegir una ciudad también fija su país. */
    private function chooseCity(string $field, ?Airport $airport, PlaceDirectory $places): void
    {
        if (! $airport instanceof Airport) {
            return;
        }

        $this->criteria[$field] = $airport->city;
        $this->lookup[$field] = $airport->city;
        $countryField = array_search(PlaceKind::Country, $this->placeFields(), true);
        if (is_string($countryField)) {
            $this->chooseCountry($countryField, $airport->country_code, $places);
        }

        $this->resetErrorBag("criteria.{$field}");
    }

    private function chooseCountry(string $field, string $code, PlaceDirectory $places): void
    {
        if ($places->countryName($code) === $code) {
            return;
        }

        $this->criteria[$field] = $code;
        $this->lookup[$field] = $places->countryName($code);
        $this->resetErrorBag("criteria.{$field}");
    }
}
