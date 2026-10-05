<?php

declare(strict_types=1);

namespace App\Modules\Finance\Data;

use Carbon\CarbonImmutable;

/** Rentabilidad de un expediente. */
final readonly class BookingProfit
{
    public function __construct(
        public string $ulid,
        public string $number,
        public string $title,
        public int $ownerId,
        public ?int $branchId,
        public CarbonImmutable $soldAt,
        public ProfitFigures $figures,
    ) {}
}
