<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Actions;

use App\Modules\Quotes\Enums\QuoteStatus;
use App\Modules\Quotes\Models\Quote;
use Carbon\CarbonImmutable;

/** Marca como vencidas las cotizaciones enviadas cuya vigencia ya pasó. Devuelve cuántas venció. */
final class ExpireQuotesAction
{
    public function execute(CarbonImmutable $now): int
    {
        $expired = 0;

        Quote::query()
            ->where('status', QuoteStatus::Sent)
            ->where('valid_until', '<=', $now)
            ->eachById(static function (Quote $quote) use (&$expired): void {
                // Se guarda modelo por modelo para que cada vencimiento quede en la auditoría.
                $quote->status = QuoteStatus::Expired;
                $quote->save();
                $expired++;
            });

        return $expired;
    }
}
