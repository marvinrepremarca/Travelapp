<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Modules\Organization\Contracts\AppSettings;
use App\Modules\Pricing\Contracts\ExchangeRates;
use App\Modules\Pricing\Data\ExchangeRateQuote;
use App\Modules\Pricing\Enums\ExchangeRateSource;
use App\Modules\Pricing\Exceptions\ExchangeRateUnavailable;
use App\Modules\Pricing\Models\ExchangeRate;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Carbon\CarbonImmutable;

/**
 * Tasa de la agencia = tasa vigente (la más reciente en o antes de la fecha; la manual prevalece sobre
 * la oficial del mismo día) + spread ⚙ en puntos básicos. Funciona en ambos sentidos del par.
 */
final readonly class DatabaseExchangeRates implements ExchangeRates
{
    private const RATE_SCALE = 8;

    private const BASIS_POINTS_PER_UNIT = 10000;

    public function __construct(private AppSettings $settings) {}

    public function quote(string $from, string $to, CarbonImmutable $date): ExchangeRateQuote
    {
        $from = mb_strtoupper($from);
        $to = mb_strtoupper($to);
        $spread = $this->settings->fxSpreadBasisPoints();

        if ($from === $to) {
            return new ExchangeRateQuote($from, $to, BigDecimal::one(), BigDecimal::one(), 0, ExchangeRateSource::Official, $date->startOfDay());
        }

        $direct = $this->latest($from, $to, $date);

        if ($direct instanceof ExchangeRate) {
            $official = $direct->decimalRate();

            return new ExchangeRateQuote($from, $to, $this->withSpread($official, $spread), $official, $spread, $direct->source, $direct->valid_on);
        }

        $inverse = $this->latest($to, $from, $date) ?? throw ExchangeRateUnavailable::forPair($from, $to, $date);
        $official = BigDecimal::one()->dividedBy($inverse->decimalRate(), self::RATE_SCALE, RoundingMode::HALF_UP);

        return new ExchangeRateQuote($from, $to, $this->withSpread($official, $spread), $official, $spread, $inverse->source, $inverse->valid_on);
    }

    public function convert(Money $amount, string $to, CarbonImmutable $date): array
    {
        $quote = $this->quote($amount->getCurrency()->getCurrencyCode(), $to, $date);

        $converted = Money::of(
            $amount->getAmount()->multipliedBy($quote->rate)->toScale(self::RATE_SCALE, RoundingMode::HALF_UP),
            $quote->to,
            roundingMode: RoundingMode::HALF_UP,
        );

        return [$converted, $quote];
    }

    private function latest(string $base, string $quote, CarbonImmutable $date): ?ExchangeRate
    {
        return ExchangeRate::query()
            ->where('base_currency', $base)
            ->where('quote_currency', $quote)
            ->where('valid_on', '<=', $date->toDateString())
            ->orderByDesc('valid_on')
            // A igual fecha, la manual (registrada por finanzas) prevalece sobre la oficial.
            ->orderByRaw('case when source = ? then 0 else 1 end', [ExchangeRateSource::Manual->value])
            ->first();
    }

    private function withSpread(BigDecimal $rate, int $spreadBasisPoints): BigDecimal
    {
        return $rate->multipliedBy(BigDecimal::of(self::BASIS_POINTS_PER_UNIT + $spreadBasisPoints)->dividedBy(self::BASIS_POINTS_PER_UNIT, self::RATE_SCALE))
            ->toScale(self::RATE_SCALE, RoundingMode::HALF_UP);
    }
}
