<?php

declare(strict_types=1);

namespace App\Modules\Search\Services;

use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * Pausa un proveedor tras N fallos seguidos durante un enfriamiento ⚙, para no hacer esperar al asesor
 * ni sobrecargar a un proveedor caído. Un éxito reinicia el conteo.
 */
final readonly class CircuitBreaker
{
    private const FAILURES_KEY = 'search:breaker:failures:';

    private const OPEN_KEY = 'search:breaker:open:';

    public function __construct(private Cache $cache) {}

    public function isOpen(string $providerKey): bool
    {
        return $this->cache->has(self::OPEN_KEY . $providerKey);
    }

    public function recordSuccess(string $providerKey): void
    {
        $this->cache->forget(self::FAILURES_KEY . $providerKey);
    }

    public function recordFailure(string $providerKey): void
    {
        $cooldown = config()->integer('travel.search.breaker_cooldown_seconds');
        $failures = (int) $this->cache->get(self::FAILURES_KEY . $providerKey, 0) + 1;
        $this->cache->put(self::FAILURES_KEY . $providerKey, $failures, $cooldown);

        if ($failures >= config()->integer('travel.search.breaker_failure_threshold')) {
            $this->cache->put(self::OPEN_KEY . $providerKey, true, $cooldown);
            $this->cache->forget(self::FAILURES_KEY . $providerKey);
        }
    }
}
