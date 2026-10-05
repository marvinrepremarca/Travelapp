<?php

declare(strict_types=1);

namespace App\Modules\Search\Contracts;

use App\Modules\Search\Data\ProviderBookingRequest;
use App\Modules\Search\Exceptions\ProviderUnavailable;
use App\Modules\Shared\Enums\ProductType;
use Brick\Money\Money;

/**
 * Puerta única para que otros módulos (Bookings) re-coticen, reserven y cancelen con el proveedor
 * de una oferta, identificado por tipo de producto y clave. Nunca conocen el adaptador.
 */
interface SupplierGateway
{
    /** @throws ProviderUnavailable */
    public function reprice(ProductType $type, string $providerKey, string $offerId): ?Money;

    /** @throws ProviderUnavailable */
    public function book(ProductType $type, string $providerKey, ProviderBookingRequest $request): string;

    /** @throws ProviderUnavailable */
    public function cancel(ProductType $type, string $providerKey, string $bookingReference): void;
}
