<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Actions;

use App\Modules\Pricing\Contracts\OfficialExchangeRateSource;
use App\Modules\Pricing\Enums\ExchangeRateSource;
use App\Modules\Pricing\Exceptions\ExchangeRateUnavailable;
use App\Modules\Pricing\Models\ExchangeRate;
use Carbon\CarbonImmutable;

/**
 * Trae la tasa oficial del día y la guarda para cada día de su vigencia (una TRM de fin de semana
 * cubre sábado a lunes). Si la fuente falla, lanza la excepción: la carga manual sigue disponible.
 */
final readonly class FetchOfficialRateAction
{
    public function __construct(
        private OfficialExchangeRateSource $source,
        private RecordExchangeRateAction $record,
    ) {}

    /**
     * @return list<ExchangeRate>
     *
     * @throws ExchangeRateUnavailable
     */
    public function execute(CarbonImmutable $date): array
    {
        $official = $this->source->rateOn($date);
        $saved = [];

        for ($day = $official->validFrom->startOfDay(); $day->lessThanOrEqualTo($official->validUntil); $day = $day->addDay()) {
            $saved[] = $this->record->execute($official->base, $official->quote, $official->rate, ExchangeRateSource::Official, $day);
        }

        return $saved;
    }
}
