<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Adapters\Fake;

use App\Modules\Search\Contracts\FlightProvider;
use App\Modules\Search\Data\FlightOffer;
use App\Modules\Search\Data\FlightSearchCriteria;
use App\Modules\Search\Data\FlightSegment;
use App\Modules\Search\Data\ProviderBookingRequest;
use App\Modules\Search\Exceptions\ProviderUnavailable;
use Brick\Money\Money;
use Carbon\CarbonImmutable;

/**
 * Proveedor de vuelos simulado, sin red y determinista (mismos criterios = mismas ofertas). Sirve para pruebas,
 * demo y para seguir trabajando si los proveedores reales fallan. Escenarios por destino: `ERR` falla, `NON` sin cupo.
 */
final readonly class FakeFlights implements FlightProvider
{
    public const KEY = 'fake';

    public const FAILING_DESTINATION = 'ERR';

    public const SOLD_OUT_DESTINATION = 'NON';

    /** Destino cuyas ofertas cambian de precio al re-cotizar antes de reservar. */
    public const PRICE_CHANGE_DESTINATION = 'PRC';

    private const CURRENCY = 'USD';

    private const CARRIER_CODE = 'ZZ';

    private const CARRIER_NAME = 'Aerolínea de prueba';

    /** Ofertas por búsqueda: directa temprana, directa tarde y con escala. */
    private const DEPARTURE_TIMES = ['06:30', '13:15', '19:40'];

    private const BASE_FARE_MINOR = 18000;

    private const FARE_STEP_MINOR = 4500;

    private const FLIGHT_MINUTES = 95;

    private const FIRST_FLIGHT_NUMBER = 100;

    private const LOCAL_DATETIME_FORMAT = 'Y-m-d\TH:i';

    public function __construct(private FakeBookingDesk $desk) {}

    public function key(): string
    {
        return self::KEY;
    }

    public function reprice(string $offerId): ?Money
    {
        return $this->desk->reprice($offerId);
    }

    public function book(ProviderBookingRequest $request): string
    {
        return $this->desk->book($request);
    }

    public function cancel(string $bookingReference): void
    {
        $this->desk->cancel($bookingReference);
    }

    public function search(FlightSearchCriteria $criteria): array
    {
        $destination = mb_strtoupper($criteria->destination);
        if ($destination === self::FAILING_DESTINATION) {
            throw ProviderUnavailable::for(self::KEY);
        }

        if ($destination === self::SOLD_OUT_DESTINATION) {
            return [];
        }

        $offers = [];
        foreach (self::DEPARTURE_TIMES as $index => $time) {
            $perPassenger = self::BASE_FARE_MINOR + self::FARE_STEP_MINOR * $index + crc32($criteria->hash()) % self::FARE_STEP_MINOR;
            $offers[] = new FlightOffer(
                providerKey: self::KEY,
                offerId: 'fake-' . substr($criteria->hash(), 0, 12) . '-' . $index,
                totalNet: Money::ofMinor($perPassenger * count($criteria->passengerAges), self::CURRENCY),
                outbound: [$this->segment($criteria->origin, $criteria->destination, $criteria->departureDate->toDateString(), $time, $index)],
                inbound: $criteria->returnDate instanceof \Carbon\CarbonImmutable ? [$this->segment($criteria->destination, $criteria->origin, $criteria->returnDate->toDateString(), $time, $index)] : [],
                refundable: $index > 0,
            );
        }

        foreach ($offers as $offer) {
            $this->desk->remember($offer->offerId, $offer->totalNet, $destination === self::PRICE_CHANGE_DESTINATION);
        }

        return $offers;
    }

    private function segment(string $from, string $to, string $date, string $time, int $index): FlightSegment
    {
        $departs = CarbonImmutable::parse("{$date} {$time}");

        return new FlightSegment(
            origin: mb_strtoupper($from),
            destination: mb_strtoupper($to),
            departsAtLocal: $departs->format(self::LOCAL_DATETIME_FORMAT),
            arrivesAtLocal: $departs->addMinutes(self::FLIGHT_MINUTES)->format(self::LOCAL_DATETIME_FORMAT),
            carrierCode: self::CARRIER_CODE,
            carrierName: self::CARRIER_NAME,
            flightNumber: self::CARRIER_CODE . (self::FIRST_FLIGHT_NUMBER + $index),
        );
    }
}
