<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Data;

use App\Modules\Shared\ValueObjects\Percentage;
use Brick\Money\Money;

/**
 * Política de cancelación de un servicio: tramos "si se cancela con N días o menos de anticipación, se cobra X %"
 * sobre el precio de venta. Gana el tramo más cercano a la fecha del servicio que aplique. No reembolsable = 100 %.
 */
final readonly class CancellationPolicy
{
    private const FULL_PENALTY_BASIS_POINTS = 10000;

    /**
     * @param  list<array{days_before: int, rate_basis_points: int}>  $tiers
     */
    public function __construct(
        public bool $nonRefundable,
        public array $tiers,
    ) {}

    /** @param array{non_refundable?: bool, tiers?: list<array{days_before: int, rate_basis_points: int}>} $data */
    public static function fromArray(array $data): self
    {
        return new self((bool) ($data['non_refundable'] ?? false), $data['tiers'] ?? []);
    }

    /** @return array{non_refundable: bool, tiers: list<array{days_before: int, rate_basis_points: int}>} */
    public function toArray(): array
    {
        $tiers = $this->tiers;
        usort($tiers, static fn(array $a, array $b): int => $b['days_before'] <=> $a['days_before']);

        return ['non_refundable' => $this->nonRefundable, 'tiers' => $tiers];
    }

    /** Penalidad si se cancela con `$daysBefore` días de anticipación (0 o negativo = el día del servicio o después). */
    public function penaltyFor(Money $sale, int $daysBefore): Money
    {
        return Percentage::fromBasisPoints($this->rateFor($daysBefore))->applyTo($sale);
    }

    public function rateFor(int $daysBefore): int
    {
        if ($this->nonRefundable) {
            return self::FULL_PENALTY_BASIS_POINTS;
        }

        $applicable = array_filter($this->tiers, static fn(array $tier): bool => max($daysBefore, 0) <= $tier['days_before']);
        if ($applicable === []) {
            return 0;
        }

        usort($applicable, static fn(array $a, array $b): int => $a['days_before'] <=> $b['days_before']);

        return $applicable[0]['rate_basis_points'];
    }
}
