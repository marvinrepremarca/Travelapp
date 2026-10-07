<?php

declare(strict_types=1);

namespace App\Modules\Portal\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\URL;

/** Enlaces firmados y con vencimiento ⚙ del portal del viajero ("enlace mágico", sin contraseña). */
final class TripLinks
{
    public const TRIP_ROUTE = 'portal.trip';

    public const ITINERARY_ROUTE = 'portal.itinerary';

    public const VOUCHER_ROUTE = 'portal.voucher';

    public function expiresAt(): CarbonImmutable
    {
        return CarbonImmutable::now()->addHours(config()->integer('travel.portal.link_ttl_hours'));
    }

    public function trip(string $bookingUlid, CarbonImmutable $expiresAt): string
    {
        return URL::temporarySignedRoute(self::TRIP_ROUTE, $expiresAt, ['booking' => $bookingUlid]);
    }

    public function itinerary(string $bookingUlid, CarbonImmutable $expiresAt): string
    {
        return URL::temporarySignedRoute(self::ITINERARY_ROUTE, $expiresAt, ['booking' => $bookingUlid]);
    }

    public function voucher(string $bookingUlid, string $itemUlid, CarbonImmutable $expiresAt): string
    {
        return URL::temporarySignedRoute(self::VOUCHER_ROUTE, $expiresAt, ['booking' => $bookingUlid, 'item' => $itemUlid]);
    }
}
