<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Actions;

use App\Modules\Quotes\Enums\QuoteStatus;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Services\QuoteItemPricer;
use App\Modules\Quotes\Services\QuoteLifecycle;
use Illuminate\Support\Facades\DB;

/**
 * Reabre una cotización enviada o vencida como borrador de la siguiente versión.
 * Recalcula todos los precios con las tarifas, reglas y tasas vigentes hoy; la versión enviada no cambia.
 */
final readonly class ReviseQuoteAction
{
    public function __construct(
        private QuoteLifecycle $lifecycle,
        private QuoteItemPricer $pricer,
    ) {}

    public function execute(Quote $quote): Quote
    {
        return DB::transaction(function () use ($quote): Quote {
            $quote = Quote::query()->whereKey($quote->id)->lockForUpdate()->firstOrFail();
            $this->lifecycle->transition($quote, QuoteStatus::Draft);
            $quote->valid_until = null;
            $quote->save();

            foreach ($quote->options()->with('items')->get() as $option) {
                foreach ($option->items as $item) {
                    $this->pricer->reprice($item, $quote);
                    $item->save();
                }
            }

            return $quote;
        });
    }
}
