<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Adapters\DatosGov;

use App\Modules\Integrations\Exceptions\ProviderRequestFailed;
use App\Modules\Integrations\Support\ProviderHttpClient;
use App\Modules\Pricing\Contracts\OfficialExchangeRateSource;
use App\Modules\Pricing\Data\OfficialRate;
use App\Modules\Pricing\Exceptions\ExchangeRateUnavailable;
use Brick\Math\BigDecimal;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * TRM (USD → COP) de datos abiertos de Colombia (Superintendencia Financiera). Ver README.md.
 * Traduce la respuesta del proveedor a OfficialRate; nada del formato externo sale de aquí.
 */
final readonly class DatosGovTrmSource implements OfficialExchangeRateSource
{
    public const PROVIDER = 'datos_gov_trm';

    private const OPERATION = 'trm_on_date';

    private const BASE = 'USD';

    private const QUOTE = 'COP';

    private const SOCRATA_DATE = 'Y-m-d\T00:00:00.000';

    public function __construct(private ProviderHttpClient $client) {}

    public function rateOn(CarbonImmutable $date): OfficialRate
    {
        $day = $date->format(self::SOCRATA_DATE);

        try {
            $response = $this->client->getJson(self::PROVIDER, self::OPERATION, config()->string('suppliers.providers.' . self::PROVIDER . '.base_url'), [
                '$where' => "vigenciadesde <= '{$day}' AND vigenciahasta >= '{$day}'",
                '$limit' => 1,
            ]);
        } catch (ProviderRequestFailed) {
            throw ExchangeRateUnavailable::sourceFailed();
        }

        $row = $response->json('0');

        try {
            if (! is_array($row) || ($row['unidad'] ?? null) !== self::QUOTE) {
                throw ExchangeRateUnavailable::forPair(self::BASE, self::QUOTE, $date);
            }

            return new OfficialRate(
                base: self::BASE,
                quote: self::QUOTE,
                rate: BigDecimal::of((string) $row['valor']),
                validFrom: CarbonImmutable::parse((string) $row['vigenciadesde'])->startOfDay(),
                validUntil: CarbonImmutable::parse((string) $row['vigenciahasta'])->startOfDay(),
            );
        } catch (ExchangeRateUnavailable $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::channel('suppliers')->warning('trm_malformed_response', ['provider' => self::PROVIDER, 'error' => $exception::class]);

            throw ExchangeRateUnavailable::sourceFailed();
        }
    }
}
