<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Services;

use App\Modules\Quotes\Enums\QuoteStatus;
use App\Modules\Quotes\Exceptions\QuoteRuleViolation;
use App\Modules\Quotes\Models\Quote;

/** Única puerta para cambiar el estado de una cotización: valida la transición contra la máquina de estados. */
final class QuoteLifecycle
{
    public function transition(Quote $quote, QuoteStatus $next): void
    {
        if (! $quote->status->canTransitionTo($next)) {
            throw QuoteRuleViolation::invalidTransition($quote->status, $next);
        }

        $quote->status = $next;
    }

    public function assertEditable(Quote $quote): void
    {
        if (! $quote->status->isEditable()) {
            throw QuoteRuleViolation::notEditable($quote->status);
        }
    }
}
