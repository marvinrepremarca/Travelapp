<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Support;

use App\Modules\Integrations\Enums\RequestOutcome;
use App\Modules\Integrations\Exceptions\ProviderRequestFailed;
use App\Modules\Integrations\Models\SupplierRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;

/**
 * Cliente HTTP común para proveedores: solo hosts en lista blanca (anti-SSRF), timeouts explícitos,
 * reintentos con espera creciente (solo para lecturas idempotentes) y registro de cada llamada.
 */
final readonly class ProviderHttpClient
{
    private const MILLISECONDS_PER_SECOND = 1000;

    private const ERROR_MAX_LENGTH = 255;

    public function __construct(private Http $http) {}

    /** @param array<string, scalar> $query */
    public function getJson(string $provider, string $operation, string $url, array $query = []): Response
    {
        $config = config()->array("suppliers.providers.{$provider}");
        $this->assertAllowedHost($url);

        $correlationId = (string) Str::uuid();
        $started = hrtime(true);
        $response = null;
        $outcome = RequestOutcome::Success;
        $error = null;

        try {
            $response = $this->http
                ->acceptJson()
                ->withHeaders(['X-Correlation-Id' => $correlationId])
                ->connectTimeout((int) $config['connect_timeout_seconds'])
                ->timeout((int) $config['timeout_seconds'])
                ->retry((int) $config['retries'], (int) $config['retry_sleep_ms'], throw: true)
                ->get($url, $query);

            if ($response->failed()) {
                [$outcome, $error] = [$response->serverError() ? RequestOutcome::ServerError : RequestOutcome::ClientError, (string) $response->status()];
            }
        } catch (ConnectionException $exception) {
            [$outcome, $error] = [RequestOutcome::Timeout, $exception->getMessage()];
        } catch (RequestException $exception) {
            $response = $exception->response;
            [$outcome, $error] = [$response->serverError() ? RequestOutcome::ServerError : RequestOutcome::ClientError, $exception->getMessage()];
        } finally {
            SupplierRequest::query()->create([
                'provider' => $provider,
                'operation' => $operation,
                'correlation_id' => $correlationId,
                'http_status' => $response?->status(),
                'outcome' => $outcome,
                'duration_ms' => intdiv(hrtime(true) - $started, self::MILLISECONDS_PER_SECOND ** 2),
                'error' => $error === null ? null : Str::limit($error, self::ERROR_MAX_LENGTH),
                'requested_at' => CarbonImmutable::now(),
            ]);
        }

        if ($outcome !== RequestOutcome::Success || $response === null) {
            throw ProviderRequestFailed::because($provider, $outcome);
        }

        return $response;
    }

    private function assertAllowedHost(string $url): void
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || ! in_array($host, config()->array('suppliers.allowed_hosts'), true)) {
            throw ProviderRequestFailed::hostNotAllowed((string) $host);
        }
    }
}
