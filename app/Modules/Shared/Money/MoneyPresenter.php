<?php

declare(strict_types=1);

namespace App\Modules\Shared\Money;

use Brick\Math\RoundingMode;
use Brick\Money\Context\CustomContext;
use Brick\Money\Money;
use Illuminate\Contracts\Config\Repository;

/**
 * Único punto de redondeo y formato de importes para mostrar o facturar.
 * Los decimales de presentación por moneda son configurables ⚙ (p. ej. COP sin decimales);
 * si no hay regla se usan los de ISO 4217.
 */
final readonly class MoneyPresenter
{
    public function __construct(private Repository $config) {}

    public function decimalsFor(string $currencyCode): int
    {
        /** @var array<string, int> $overrides */
        $overrides = $this->config->array('travel.money.presentation_decimals');

        return $overrides[$currencyCode] ?? Money::zero($currencyCode)->getCurrency()->getDefaultFractionDigits();
    }

    /** Redondea HALF_UP a los decimales de presentación de la moneda. */
    public function round(Money $amount): Money
    {
        $decimals = $this->decimalsFor($amount->getCurrency()->getCurrencyCode());

        return $amount->to(new CustomContext($decimals), RoundingMode::HALF_UP);
    }

    public function format(Money $amount, ?string $locale = null): string
    {
        $rounded = $this->round($amount);

        return $rounded->formatToLocale(
            $locale ?? $this->config->string('travel.agency.locale'),
            allowWholeNumber: $rounded->getAmount()->getScale() === 0,
        );
    }
}
