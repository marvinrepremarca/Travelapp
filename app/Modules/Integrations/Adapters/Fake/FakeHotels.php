<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Adapters\Fake;

use App\Modules\Search\Contracts\HotelProvider;
use App\Modules\Search\Data\HotelOffer;
use App\Modules\Search\Data\HotelSearchCriteria;
use App\Modules\Search\Enums\BoardType;
use App\Modules\Search\Exceptions\ProviderUnavailable;
use Brick\Money\Money;

/**
 * Proveedor de hoteles simulado, sin red y determinista. Escenarios por ciudad: `error` falla, `agotado` sin cupo.
 */
final class FakeHotels implements HotelProvider
{
    public const KEY = 'fake';

    public const FAILING_CITY = 'error';

    public const SOLD_OUT_CITY = 'agotado';

    private const CURRENCY = 'COP';

    /** Nombre, estrellas, régimen y tarifa por noche (unidades menores) de los hoteles simulados. */
    private const HOTELS = [
        ['Hotel Plaza de Prueba', 3, BoardType::RoomOnly, 28000000],
        ['Resort Caribe de Prueba', 4, BoardType::Breakfast, 42000000],
        ['Gran Hotel de Prueba', 5, BoardType::AllInclusive, 86000000],
    ];

    private const FREE_CANCELLATION_DAYS_BEFORE = 3;

    public function key(): string
    {
        return self::KEY;
    }

    public function search(HotelSearchCriteria $criteria): array
    {
        $city = mb_strtolower(trim($criteria->city));
        if ($city === self::FAILING_CITY) {
            throw ProviderUnavailable::for(self::KEY);
        }

        if ($city === self::SOLD_OUT_CITY) {
            return [];
        }

        $offers = [];
        foreach (self::HOTELS as $index => [$name, $stars, $board, $nightlyMinor]) {
            $refundable = $index !== 0;
            $offers[] = new HotelOffer(
                providerKey: self::KEY,
                offerId: 'fake-' . substr($criteria->hash(), 0, 12) . '-' . $index,
                hotelName: $name . ' ' . $criteria->city,
                roomName: __('search.fake.room', ['guests' => count($criteria->guestAges)]),
                totalNet: Money::ofMinor($nightlyMinor * max($criteria->nights(), 1), self::CURRENCY),
                refundable: $refundable,
                boardType: $board,
                stars: $stars,
                freeCancellationUntil: $refundable ? $criteria->checkIn->subDays(self::FREE_CANCELLATION_DAYS_BEFORE) : null,
            );
        }

        return $offers;
    }
}
