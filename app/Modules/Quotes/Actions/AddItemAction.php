<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Actions;

use App\Modules\Quotes\Data\QuoteItemData;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Models\QuoteItem;
use App\Modules\Quotes\Models\QuoteOption;
use App\Modules\Quotes\Services\QuoteItemPricer;
use App\Modules\Quotes\Services\QuoteLifecycle;

/** Agrega un ítem a una opción del borrador con su precio calculado en el servidor. */
final readonly class AddItemAction
{
    public function __construct(
        private QuoteLifecycle $lifecycle,
        private QuoteItemPricer $pricer,
    ) {}

    public function execute(Quote $quote, QuoteOption $option, QuoteItemData $data): QuoteItem
    {
        $this->lifecycle->assertEditable($quote);

        $item = new QuoteItem(['option_id' => $option->id]);
        $this->pricer->price($item, $data, $quote);
        $item->save();

        return $item;
    }
}
