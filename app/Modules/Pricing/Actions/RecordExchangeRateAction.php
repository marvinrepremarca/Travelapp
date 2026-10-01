<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Actions;

use App\Modules\Pricing\Enums\ExchangeRateSource;
use App\Modules\Pricing\Exceptions\InvalidExchangeRate;
use App\Modules\Pricing\Models\ExchangeRate;
use Brick\Math\BigDecimal;
use Carbon\CarbonImmutable;

/** Registra (o corrige) la tasa de un par para un día y una fuente. */
final class RecordExchangeRateAction
{
    private const RATE_SCALE = 8;

    public function execute(string $base, string $quote, BigDecimal $rate, ExchangeRateSource $source, CarbonImmutable $validOn, ?int $recordedBy = null): ExchangeRate
    {
        $base = mb_strtoupper($base);
        $quote = mb_strtoupper($quote);

        if ($base === $quote || ! $rate->isPositive()) {
            throw InvalidExchangeRate::make();
        }

        return ExchangeRate::query()->updateOrCreate(
            ['base_currency' => $base, 'quote_currency' => $quote, 'source' => $source, 'valid_on' => $validOn->toDateString()],
            ['rate' => (string) $rate->toScale(self::RATE_SCALE), 'recorded_by' => $recordedBy],
        );
    }
}
