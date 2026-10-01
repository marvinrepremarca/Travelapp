<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Console;

use App\Modules\Pricing\Actions\FetchOfficialRateAction;
use App\Modules\Pricing\Exceptions\ExchangeRateUnavailable;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class FetchOfficialRateCommand extends Command
{
    protected $signature = 'pricing:fetch-official-rate {date? : Fecha (AAAA-MM-DD); por defecto hoy}';

    protected $description = 'Descarga la TRM oficial y la guarda como tasa del día.';

    public function handle(FetchOfficialRateAction $fetch): int
    {
        $date = CarbonImmutable::parse((string) ($this->argument('date') ?? 'today'));

        try {
            $saved = $fetch->execute($date);
        } catch (ExchangeRateUnavailable $exception) {
            Log::warning('official_rate_unavailable', ['date' => $date->toDateString(), 'message' => $exception->getMessage()]);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(__('pricing.official_rate_saved', ['count' => count($saved), 'rate' => $saved[0]->rate ?? '']));

        return self::SUCCESS;
    }
}
