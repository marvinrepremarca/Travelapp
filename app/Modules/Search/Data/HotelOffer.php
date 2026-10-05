<?php

declare(strict_types=1);

namespace App\Modules\Search\Data;

use App\Modules\Search\Enums\BoardType;
use Brick\Money\Money;
use Carbon\CarbonImmutable;

/** Tarifa de hotel normalizada por estadía completa. */
final readonly class HotelOffer
{
    public function __construct(
        public string $providerKey,
        public string $offerId,
        public string $hotelName,
        public string $roomName,
        public Money $totalNet,
        public bool $refundable,
        public BoardType $boardType = BoardType::Unknown,
        public ?int $stars = null,
        public ?CarbonImmutable $freeCancellationUntil = null,
    ) {}
}
