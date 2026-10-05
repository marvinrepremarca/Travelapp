<?php

declare(strict_types=1);

namespace App\Modules\Search\Contracts;

use App\Modules\Search\Data\ProviderBookingRequest;
use App\Modules\Search\Exceptions\ProviderUnavailable;
use Brick\Money\Money;

/** Operaciones de compromiso con el proveedor, comunes a todos los productos (ADR-0006, regla integrations.md). */
interface BookableProvider
{
    public function key(): string;

    /**
     * Precio vigente de la oferta antes de reservar; null si ya no está disponible.
     *
     * @throws ProviderUnavailable
     */
    public function reprice(string $offerId): ?Money;

    /**
     * Reserva la oferta. Repetir la misma `idempotencyKey` devuelve la misma reserva (nunca doble reserva).
     *
     * @return string referencia de la reserva en el proveedor
     *
     * @throws ProviderUnavailable
     */
    public function book(ProviderBookingRequest $request): string;

    /** @throws ProviderUnavailable */
    public function cancel(string $bookingReference): void;
}
