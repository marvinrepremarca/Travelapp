<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Actions;

use App\Modules\Quotes\Exceptions\QuoteRuleViolation;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Models\QuoteOption;
use App\Modules\Quotes\Services\QuoteLifecycle;

/** Quita una opción (con sus ítems) de un borrador. Siempre queda al menos una. */
final readonly class RemoveOptionAction
{
    public function __construct(private QuoteLifecycle $lifecycle) {}

    public function execute(Quote $quote, QuoteOption $option): void
    {
        $this->lifecycle->assertEditable($quote);

        if ($quote->options()->count() <= 1) {
            throw QuoteRuleViolation::lastOption();
        }

        $option->delete();
    }
}
