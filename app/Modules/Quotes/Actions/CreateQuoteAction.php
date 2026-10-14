<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Actions;

use App\Modules\Crm\Data\LeadProspect;
use App\Modules\Customers\Models\Customer;
use App\Modules\Identity\Models\User;
use App\Modules\Quotes\Enums\QuoteStatus;
use App\Modules\Quotes\Exceptions\QuoteRuleViolation;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Models\QuoteOption;
use App\Modules\Shared\Enums\SalesChannel;
use Illuminate\Support\Facades\DB;

/** Crea una cotización en borrador, de quien la crea y en su sucursal, con la opción A lista para cargar ítems. */
final class CreateQuoteAction
{
    /**
     * Se dirige a un cliente o, si aún no lo es, al prospecto de un lead (sin registrarlo como cliente).
     *
     * @throws QuoteRuleViolation sin cliente ni prospecto
     */
    public function execute(User $actor, ?Customer $customer, string $title, string $saleCurrency, SalesChannel $channel, ?string $leadUlid = null, ?LeadProspect $prospect = null): Quote
    {
        if (! $customer instanceof Customer && ! $prospect instanceof LeadProspect) {
            throw QuoteRuleViolation::recipientRequired();
        }

        return DB::transaction(static function () use ($actor, $customer, $title, $saleCurrency, $channel, $leadUlid, $prospect): Quote {
            $quote = new Quote([
                'customer_id' => $customer?->id,
                'title' => $title,
                'sale_currency' => mb_strtoupper($saleCurrency),
                'sales_channel' => $channel,
                'prospect_name' => $customer instanceof Customer ? null : $prospect->name,
                'prospect_email' => $customer instanceof Customer ? null : $prospect->email,
                'prospect_phone' => $customer instanceof Customer ? null : $prospect->phone,
            ]);
            $quote->lead_ulid = $leadUlid ?? $prospect?->leadUlid;
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
