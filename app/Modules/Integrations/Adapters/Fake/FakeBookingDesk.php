<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Adapters\Fake;

use App\Modules\Search\Data\ProviderBookingRequest;
use Brick\Money\Money;
use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * "Mostrador" simulado común a los proveedores Fake: recuerda las ofertas buscadas (vencen como en un proveedor real),
 * re-cotiza, reserva de forma idempotente y cancela. Sin red.
 */
final readonly class FakeBookingDesk
{
    private const OFFER_KEY = 'fake-provider:offer:';

    private const BOOKING_KEY = 'fake-provider:booking:';

    private const REFERENCE_PREFIX = 'FAKE-';

    private const REFERENCE_LENGTH = 8;

    /** Escenario de cambio de precio: la oferta sube este porcentaje (en puntos básicos) al re-cotizar. */
    private const PRICE_CHANGE_BASIS_POINTS = 1000;

    private const BASIS_POINTS_PER_UNIT = 10000;

    public function __construct(private Cache $cache) {}

    public function remember(string $offerId, Money $net, bool $priceWillChange): void
    {
        $this->cache->put(self::OFFER_KEY . $offerId, [
            'minor' => $net->getMinorAmount()->toInt(),
            'currency' => $net->getCurrency()->getCurrencyCode(),
            'changes' => $priceWillChange,
        ], config()->integer('travel.search.cache_ttl_seconds'));
    }

    public function reprice(string $offerId): ?Money
    {
        /** @var array{minor: int, currency: string, changes: bool}|null $offer */
        $offer = $this->cache->get(self::OFFER_KEY . $offerId);
        if ($offer === null) {
            return null;
        }

        $net = Money::ofMinor($offer['minor'], $offer['currency']);

        return $offer['changes'] ? $net->plus($net->multipliedBy(self::PRICE_CHANGE_BASIS_POINTS)->dividedBy(self::BASIS_POINTS_PER_UNIT, \Brick\Math\RoundingMode::HALF_UP)) : $net;
    }

    public function book(ProviderBookingRequest $request): string
    {
        $existing = $this->cache->get(self::BOOKING_KEY . $request->idempotencyKey);
        if (is_string($existing)) {
            return $existing;
        }

        $reference = self::REFERENCE_PREFIX . mb_strtoupper(substr(hash('sha256', $request->idempotencyKey), 0, self::REFERENCE_LENGTH));
        $this->cache->forever(self::BOOKING_KEY . $request->idempotencyKey, $reference);

        return $reference;
    }

    public function cancel(string $bookingReference): void
    {
        // El proveedor simulado no guarda estado de reservas más allá de la idempotencia: cancelar siempre tiene éxito.
        unset($bookingReference);
    }
}
