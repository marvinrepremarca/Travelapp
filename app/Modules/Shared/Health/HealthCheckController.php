<?php

declare(strict_types=1);

namespace App\Modules\Shared\Health;

use App\Modules\Shared\Enums\HealthCheck;
use App\Modules\Shared\Enums\HealthStatus;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Health check para el balanceador y el monitoreo: verifica base de datos y caché.
 * Responde 200 o 503 sin exponer detalles internos; el motivo del fallo va al log.
 */
final readonly class HealthCheckController
{
    private const CACHE_PROBE_TTL_SECONDS = 10;

    public function __construct(
        private ConnectionInterface $database,
        private Cache $cache,
    ) {}

    public function __invoke(): JsonResponse
    {
        $checks = [
            HealthCheck::Database->value => $this->check(HealthCheck::Database, fn(): bool => $this->database->select('select 1 as ok') !== []),
            HealthCheck::Cache->value => $this->check(HealthCheck::Cache, function (): bool {
                $key = 'health:' . Str::ulid();
                $this->cache->put($key, true, self::CACHE_PROBE_TTL_SECONDS);

                return $this->cache->pull($key) === true;
            }),
        ];

        $healthy = ! in_array(HealthStatus::Down->value, $checks, true);

        return new JsonResponse(
            ['status' => ($healthy ? HealthStatus::Up : HealthStatus::Down)->value, 'checks' => $checks],
            $healthy ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE,
        );
    }

    /** @param callable(): bool $probe */
    private function check(HealthCheck $name, callable $probe): string
    {
        try {
            return ($probe() ? HealthStatus::Up : HealthStatus::Down)->value;
        } catch (Throwable $exception) {
            Log::error('health_check_failed', ['check' => $name->value, 'exception' => $exception::class, 'message' => $exception->getMessage()]);

            return HealthStatus::Down->value;
        }
    }
}
