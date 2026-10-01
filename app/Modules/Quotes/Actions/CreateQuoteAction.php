<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Actions;

use App\Modules\Crm\Models\Customer;
use App\Modules\Identity\Models\User;
use App\Modules\Quotes\Enums\QuoteStatus;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Models\QuoteOption;
use App\Modules\Shared\Enums\SalesChannel;
use Illuminate\Support\Facades\DB;

/** Crea una cotización en borrador, de quien la crea y en su sucursal, con la opción A lista para cargar ítems. */
final class CreateQuoteAction
{
    public function execute(User $actor, Customer $customer, string $title, string $saleCurrency, SalesChannel $channel): Quote
    {
        return DB::transaction(static function () use ($actor, $customer, $title, $saleCurrency, $channel): Quote {
            $quote = new Quote([
                'customer_id' => $customer->id,
                'title' => $title,
                'sale_currency' => mb_strtoupper($saleCurrency),
                'sales_channel' => $channel,
            ]);
            $quote->owner_id = $actor->id;
            $quote->branch_id = $actor->branch_id;
            $quote->status = QuoteStatus::Draft;
            $quote->save();

            // El consecutivo sale del id para que sea único sin bloquear la tabla.
            $quote->number = config()->string('travel.quotes.number_prefix')
                . str_pad((string) $quote->id, config()->integer('travel.quotes.number_digits'), '0', STR_PAD_LEFT);
            $quote->save();

            QuoteOption::query()->create(['quote_id' => $quote->id, 'label' => QuoteOption::FIRST_LABEL, 'title' => $title]);

            return $quote;
        });
    }
}
