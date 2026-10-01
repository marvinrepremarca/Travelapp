<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Actions;

use App\Modules\Quotes\Enums\QuoteStatus;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Services\QuoteLifecycle;

/** Cancela una cotización no aceptada. Queda en la auditoría; no se borra. */
final readonly class CancelQuoteAction
{
    public function __construct(private QuoteLifecycle $lifecycle) {}

    public function execute(Quote $quote): Quote
    {
        $this->lifecycle->transition($quote, QuoteStatus::Cancelled);
        $quote->save();

        return $quote;
    }
}
