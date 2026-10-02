<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Services;

use App\Modules\Catalog\Models\CatalogProduct;
use App\Modules\Quotes\Contracts\AcceptedQuotes;
use App\Modules\Quotes\Data\AcceptedOption;
use App\Modules\Quotes\Enums\QuoteStatus;
use App\Modules\Quotes\Exceptions\QuoteRuleViolation;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Models\QuoteItem;
use App\Modules\Quotes\Models\QuoteOption;

final class EloquentAcceptedQuotes implements AcceptedQuotes
{
    public function acceptedOption(string $quoteUlid): AcceptedOption
    {
        $quote = Quote::query()->where('ulid', $quoteUlid)->firstOrFail();
        if ($quote->status !== QuoteStatus::Accepted || $quote->accepted_option_id === null) {
            throw QuoteRuleViolation::notAccepted();
        }

        // Tras aceptar la cotización queda bloqueada: los ítems vigentes son los de la versión aceptada.
        $option = QuoteOption::query()->with('items')->whereKey($quote->accepted_option_id)->firstOrFail();
        $productUlids = CatalogProduct::query()
            ->whereIn('id', $option->items->pluck('catalog_product_id')->filter()->all())
            ->pluck('ulid', 'id');

        return new AcceptedOption(
            quoteUlid: $quote->ulid,
            quoteNumber: (string) $quote->number,
            version: (int) $quote->accepted_version,
            customerId: $quote->customer_id,
            ownerId: $quote->owner_id,
            branchId: $quote->branch_id,
            saleCurrency: $quote->sale_currency,
            title: $quote->title,
            items: array_values($option->items->map(static fn(QuoteItem $item): array => [
                'kind' => $item->kind->value,
                'product_type' => $item->product_type->value,
                'description' => $item->description,
                'catalog_product_ulid' => $item->catalog_product_id === null ? null : (string) $productUlids->get($item->catalog_product_id),
                'supplier_id' => $item->supplier_id,
                'destination_country' => $item->destination_country,
                'service_date' => $item->service_date->toDateString(),
                'nights' => $item->nights,
                'passenger_ages' => $item->passenger_ages,
                'net_amount_minor' => $item->net_amount_minor,
                'net_currency' => $item->net_currency,
                'sale_amount_minor' => $item->sale_amount_minor,
                'margin_amount_minor' => $item->margin_amount_minor,
                'price_breakdown' => $item->price_breakdown,
            ])->all()),
        );
    }
}
