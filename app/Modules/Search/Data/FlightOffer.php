<?php

declare(strict_types=1);

namespace App\Modules\Search\Data;

use Brick\Money\Money;
use Carbon\CarbonImmutable;

/** Oferta de vuelo normalizada. `offerId` es del proveedor y solo tiene sentido junto con `providerKey`. */
final readonly class FlightOffer
{
    /**
     * @param  list<FlightSegment>  $outbound
     * @param  list<FlightSegment>  $inbound
     */
    public function __construct(
        public string $providerKey,
        public string $offerId,
        public Money $totalNet,
        public array $outbound,
        public array $inbound,
        public bool $refundable,
        public ?CarbonImmutable $expiresAt = null,
    ) {}

    public function stops(): int
    {
        return max(count($this->outbound) - 1, 0);
    }
}
