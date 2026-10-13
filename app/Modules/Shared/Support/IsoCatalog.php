<?php

declare(strict_types=1);

namespace App\Modules\Shared\Support;

use Collator;
use ResourceBundle;

/**
 * Catálogos ISO de países (alfa-2) y monedas (alfa-3) con nombres en el idioma de la aplicación, leídos de ICU.
 * La persona elige por nombre; la aplicación guarda el código ISO que necesitan los proveedores y la facturación.
 */
final class IsoCatalog
{
    private const COUNTRIES_BUNDLE = 'ICUDATA-region';

    private const CURRENCIES_BUNDLE = 'ICUDATA-curr';

    private const COUNTRY_CODE_PATTERN = '/^[A-Z]{2}$/';

    private const CURRENCY_CODE_PATTERN = '/^[A-Z]{3}$/';

    /** Regiones de ICU que no son países (Unión Europea, Naciones Unidas, códigos privados, etc.). */
    private const NOT_COUNTRIES = ['AC', 'CP', 'DG', 'EA', 'EU', 'EZ', 'IC', 'QO', 'TA', 'UN', 'XA', 'XB', 'ZZ'];

    /** @var array<string, string>|null */
    private ?array $countries = null;

    /** @var array<string, string>|null */
    private ?array $currencies = null;

    /** @return array<string, string> código ISO 3166-1 alfa-2 → nombre, ordenado por nombre */
    public function countries(): array
    {
        if ($this->countries !== null) {
            return $this->countries;
        }

        $countries = [];
        $regions = $this->bundle(self::COUNTRIES_BUNDLE)?->get('Countries');
        if ($regions instanceof ResourceBundle) {
            foreach ($regions as $code => $name) {
                if (is_string($code) && is_string($name) && preg_match(self::COUNTRY_CODE_PATTERN, $code) === 1 && ! in_array($code, self::NOT_COUNTRIES, true)) {
                    $countries[$code] = $name;
                }
            }
        }

        return $this->countries = $this->sortByName($countries);
    }

    /** @return array<string, string> código ISO 4217 → "nombre (CÓDIGO)", ordenado por nombre */
    public function currencies(): array
    {
        if ($this->currencies !== null) {
            return $this->currencies;
        }

        $currencies = [];
        $names = $this->bundle(self::CURRENCIES_BUNDLE)?->get('Currencies');
        if ($names instanceof ResourceBundle) {
            foreach ($names as $code => $entry) {
                if (is_string($code) && preg_match(self::CURRENCY_CODE_PATTERN, $code) === 1 && $entry instanceof ResourceBundle && is_string($entry->get(1))) {
                    $currencies[$code] = ucfirst($entry->get(1)) . " ({$code})";
                }
            }
        }

        return $this->currencies = $this->sortByName($currencies);
    }

    private function bundle(string $name): ?ResourceBundle
    {
        $bundle = ResourceBundle::create(app()->getLocale(), $name);

        return $bundle instanceof ResourceBundle ? $bundle : null;
    }

    /**
     * @param  array<string, string>  $labels
     * @return array<string, string>
     */
    private function sortByName(array $labels): array
    {
        $collator = new Collator(app()->getLocale());
        $collator->asort($labels);

        return $labels;
    }
}
