<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Actions;

use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Models\QuoteItem;
use App\Modules\Quotes\Services\QuoteLifecycle;

final readonly class RemoveItemAction
{
    public function __construct(private QuoteLifecycle $lifecycle) {}

    public function execute(Quote $quote, QuoteItem $item): void
    {
        $this->lifecycle->assertEditable($quote);
        $item->delete();
    }
}
