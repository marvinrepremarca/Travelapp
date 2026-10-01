<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Actions;

use App\Modules\Quotes\Exceptions\QuoteRuleViolation;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Models\QuoteOption;
use App\Modules\Quotes\Services\QuoteLifecycle;

/** Agrega la siguiente opción (B, C…) hasta el máximo configurado. */
final readonly class AddOptionAction
{
    public function __construct(private QuoteLifecycle $lifecycle) {}

    public function execute(Quote $quote, string $title): QuoteOption
    {
        $this->lifecycle->assertEditable($quote);

        $max = config()->integer('travel.quotes.max_options');
        $labels = $quote->options()->pluck('label')->all();
        if (count($labels) >= $max) {
            throw QuoteRuleViolation::tooManyOptions($max);
        }

        $next = QuoteOption::FIRST_LABEL;
        while (in_array($next, $labels, true)) {
            $next = chr(ord($next) + 1);
        }

        return QuoteOption::query()->create(['quote_id' => $quote->id, 'label' => $next, 'title' => $title]);
    }
}
