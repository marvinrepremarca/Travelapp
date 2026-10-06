<?php

declare(strict_types=1);

namespace App\Modules\Reports\Data;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/** Embudo comercial del período: leads → cotizaciones enviadas → aceptadas → expedientes. */
final readonly class Funnel
{
    private const PERCENT = 100;

    private const RATE_SCALE = 1;

    public function __construct(
        public int $leads,
        public int $quotesSent,
        public int $quotesAccepted,
        public int $bookings,
    ) {}

    /** KPI Conversión de cotizaciones = aceptadas / enviadas. */
    public function conversion(): ?string
    {
        return $this->quotesSent === 0
            ? null
            : (string) BigDecimal::of($this->quotesAccepted)->multipliedBy(self::PERCENT)->dividedBy($this->quotesSent, self::RATE_SCALE, RoundingMode::HALF_UP);
    }
}
