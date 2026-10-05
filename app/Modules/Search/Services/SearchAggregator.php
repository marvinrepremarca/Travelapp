<?php

declare(strict_types=1);

namespace App\Modules\Search\Services;

use App\Modules\Search\Contracts\FlightProvider;
use App\Modules\Search\Contracts\HotelProvider;
use App\Modules\Search\Data\FlightOffer;
use App\Modules\Search\Data\FlightSearchCriteria;
use App\Modules\Search\Data\HotelOffer;
use App\Modules\Search\Data\HotelSearchCriteria;
use App\Modules\Search\Data\SearchResult;
use App\Modules\Search\Exceptions\ProviderUnavailable;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Log;

/**
 * Consulta los proveedores activos por configuración (ADR-0006), tolera fallos individuales y cachea por criterios.
 * El dominio solo conoce los puertos: cambiar de proveedor es cambiar `TRAVEL_FLIGHT_PROVIDERS` / `TRAVEL_HOTEL_PROVIDERS`.
 */
final readonly class SearchAggregator
{
    private const FLIGHTS_CACHE_KEY = 'search:flights:';

    private const HOTELS_CACHE_KEY = 'search:hotels:';

    public function __construct(
        private Container $container,
        private CircuitBreaker $breaker,
        private Cache $cache,
    ) {}

    public function flights(FlightSearchCriteria $criteria): SearchResult
    {
        /** @var list<FlightProvider> $providers */
        $providers = $this->enabled(FlightProvider::TAG, config()->array('travel.search.flight_providers'));

        return $this->cached(self::FLIGHTS_CACHE_KEY . $criteria->hash(), fn(): SearchResult => $this->collect(
            $providers,
            static fn(FlightProvider $provider): array => $provider->search($criteria),
            static fn(FlightOffer $a, FlightOffer $b): int => $a->totalNet->compareTo($b->totalNet),
        ));
    }

    public function hotels(HotelSearchCriteria $criteria): SearchResult
    {
        /** @var list<HotelProvider> $providers */
        $providers = $this->enabled(HotelProvider::TAG, config()->array('travel.search.hotel_providers'));

        return $this->cached(self::HOTELS_CACHE_KEY . $criteria->hash(), fn(): SearchResult => $this->collect(
            $providers,
            static fn(HotelProvider $provider): array => $provider->search($criteria),
            static fn(HotelOffer $a, HotelOffer $b): int => $a->totalNet->compareTo($b->totalNet),
        ));
    }

    /**
     * @template TProvider of FlightProvider|HotelProvider
     * @template TOffer of FlightOffer|HotelOffer
     *
     * @param  list<TProvider>  $providers
     * @param  callable(TProvider): list<TOffer>  $search
     * @param  callable(TOffer, TOffer): int  $order
     */
    private function collect(array $providers, callable $search, callable $order): SearchResult
    {
        $offers = [];
        $unavailable = [];

        foreach ($providers as $provider) {
            if ($this->breaker->isOpen($provider->key())) {
                $unavailable[] = $provider->key();

                continue;
            }

            try {
                $offers = [...$offers, ...$search($provider)];
                $this->breaker->recordSuccess($provider->key());
            } catch (ProviderUnavailable $exception) {
                $this->breaker->recordFailure($provider->key());
                $unavailable[] = $provider->key();
                Log::warning('search_provider_unavailable', ['provider' => $provider->key(), 'message' => $exception->getMessage()]);
            }
        }

        // Money de monedas distintas no se compara: se agrupa por moneda y luego se ordena por precio.
        usort($offers, static function (mixed $a, mixed $b) use ($order): int {
            $byCurrency = strcmp($a->totalNet->getCurrency()->getCurrencyCode(), $b->totalNet->getCurrency()->getCurrencyCode());

            return $byCurrency !== 0 ? $byCurrency : $order($a, $b);
        });

        return new SearchResult($offers, $unavailable);
    }

    /**
     * @param  callable(): SearchResult  $search
     */
    private function cached(string $key, callable $search): SearchResult
    {
        $cached = $this->cache->get($key);
        if ($cached instanceof SearchResult) {
            return $cached;
        }

        $result = $search();
        // Solo se cachea una búsqueda completa: si un proveedor falló, el siguiente intento puede traer sus ofertas.
        if ($result->unavailableProviders === []) {
            $this->cache->put($key, $result, config()->integer('travel.search.cache_ttl_seconds'));
        }

        return $result;
    }

    /**
     * Adaptadores registrados con la etiqueta, en el orden de la configuración. Una clave desconocida se ignora.
     *
     * @param  array<int|string, mixed>  $keys
     * @return list<object>
     */
    private function enabled(string $tag, array $keys): array
    {
        $registered = [];
        foreach ($this->container->tagged($tag) as $provider) {
            /** @var FlightProvider|HotelProvider $provider */
            $registered[$provider->key()] = $provider;
        }

        $enabled = [];
        foreach ($keys as $key) {
            if (isset($registered[(string) $key])) {
                $enabled[] = $registered[(string) $key];
            }
        }

        return $enabled;
    }
}
