<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Console;

use App\Modules\Quotes\Actions\ExpireQuotesAction;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

final class ExpireQuotesCommand extends Command
{
    protected $signature = 'quotes:expire';

    protected $description = 'Marca como vencidas las cotizaciones enviadas cuya vigencia terminó.';

    public function handle(ExpireQuotesAction $expire): int
    {
        $this->info(__('quotes.expired_count', ['count' => $expire->execute(CarbonImmutable::now())]));

        return self::SUCCESS;
    }
}
