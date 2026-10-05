<?php

declare(strict_types=1);

namespace App\Modules\Search\Services;

use App\Modules\Search\Contracts\BookableProvider;
use App\Modules\Search\Contracts\FlightProvider;
use App\Modules\Search\Contracts\HotelProvider;
use App\Modules\Search\Contracts\SupplierGateway;
use App\Modules\Search\Data\ProviderBookingRequest;
use App\Modules\Search\Exceptions\ProviderUnavailable;
use App\Modules\Shared\Enums\ProductType;
use Brick\Money\Money;
use Illuminate\Contracts\Container\Container;

/** Resuelve el adaptador registrado para el producto y la clave; si no existe, el proveedor se considera no disponible. */
final readonly class TaggedSupplierGateway implements SupplierGateway
{
    public function __construct(private Container $container) {}

    public function reprice(ProductType $type, string $providerKey, string $offerId): ?Money
    {
        return $this->provider($type, $providerKey)->reprice($offerId);
    }

    public function book(ProductType $type, string $providerKey, ProviderBookingRequest $request): string
    {
        return $this->provider($type, $providerKey)->book($request);
    }

    public function cancel(ProductType $type, string $providerKey, string $bookingReference): void
    {
        $this->provider($type, $providerKey)->cancel($bookingReference);
    }

    private function provider(ProductType $type, string $providerKey): BookableProvider
    {
        $tag = match ($type) {
            ProductType::Flight => FlightProvider::TAG,
            ProductType::Hotel => HotelProvider::TAG,
            default => null,
        };

        foreach ($tag === null ? [] : $this->container->tagged($tag) as $provider) {
            if ($provider instanceof BookableProvider && $provider->key() === $providerKey) {
                return $provider;
            }
        }

        throw ProviderUnavailable::for($providerKey);
    }
}
