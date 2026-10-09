<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Services;

use App\Modules\Catalog\Contracts\CatalogRates;
use App\Modules\Catalog\Models\CatalogProduct;
use App\Modules\Pricing\Contracts\PriceCalculator;
use App\Modules\Pricing\Data\PriceRequest;
use App\Modules\Quotes\Data\QuoteItemData;
use App\Modules\Quotes\Enums\QuoteItemKind;
use App\Modules\Quotes\Exceptions\QuoteRuleViolation;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Models\QuoteItem;
use Brick\Money\Money;

/**
 * Calcula en el servidor neto, venta y margen de un ítem: el neto sale del catálogo o del valor digitado
 * y la venta de las reglas de Pricing. Nunca se confía en montos del cliente.
 */
final readonly class QuoteItemPricer
{
    public function __construct(
        private CatalogRates $catalogRates,
        private PriceCalculator $calculator,
    ) {}

    /** Llena (o recalcula) los campos de precio del ítem sin guardarlo. */
    public function price(QuoteItem $item, QuoteItemData $data, Quote $quote): void
    {
        $item->fill([
            'kind' => $data->kind,
            'service_date' => $data->serviceDate->toDateString(),
            'nights' => $data->nights,
            'passenger_ages' => $data->passengerAges,
        ]);

        $net = $data->kind === QuoteItemKind::Catalog ? $this->fillFromCatalog($item, $data) : $this->fillManual($item, $data);

        $breakdown = $this->calculator->calculate(new PriceRequest(
            supplierNet: $net,
            saleCurrency: $quote->sale_currency,
            productType: $item->product_type,
            channel: $quote->sales_channel,
            serviceDate: $data->serviceDate,
            passengers: count($data->passengerAges),
            nights: $data->nights,
            supplierId: $item->supplier_id,
            destinationCountry: $item->destination_country,
        ));

        $item->net_amount_minor = $net->getMinorAmount()->toInt();
        $item->net_currency = $net->getCurrency()->getCurrencyCode();
        $item->sale_amount_minor = $breakdown->total()->getMinorAmount()->toInt();
        $item->margin_amount_minor = $breakdown->margin()->getMinorAmount()->toInt();
        $item->price_breakdown = $breakdown->snapshot($quote->sale_currency);
    }

    /** Reconstruye los datos del ítem guardado para recalcularlo con tarifas y reglas vigentes. */
    public function reprice(QuoteItem $item, Quote $quote): void
    {
        $this->price($item, new QuoteItemData(
            kind: $item->kind,
            serviceDate: $item->service_date,
            passengerAges: $item->passenger_ages,
            nights: $item->nights,
            catalogProductUlid: $item->kind === QuoteItemKind::Catalog ? CatalogProduct::query()->whereKey($item->catalog_product_id)->value('ulid') : null,
            productType: $item->product_type,
            description: $item->description,
            manualNet: $item->kind === QuoteItemKind::Manual ? $item->netAmount() : null,
            supplierId: $item->supplier_id,
            destinationCountry: $item->destination_country,
            providerKey: $item->provider_key,
            providerOfferId: $item->provider_offer_id,
        ), $quote);
    }

    private function fillFromCatalog(QuoteItem $item, QuoteItemData $data): Money
    {
        $product = CatalogProduct::query()->where('ulid', (string) $data->catalogProductUlid)->firstOrFail();
        $net = $this->catalogRates->netPriceFor($product->ulid, $data->serviceDate, $data->passengerAges)->total;

        $item->fill([
            'catalog_product_id' => $product->id,
            'product_type' => $product->product_type,
            'description' => $product->name,
            'supplier_id' => $product->supplier_id,
            'provider_key' => null,
            'provider_offer_id' => null,
            'destination_country' => $product->destination_country,
        ]);

        return $net;
    }

    private function fillManual(QuoteItem $item, QuoteItemData $data): Money
    {
        $item->fill([
            'catalog_product_id' => null,
            'product_type' => $data->productType,
            'description' => $data->description,
            'supplier_id' => $data->supplierId,
            'provider_key' => $data->providerKey,
            'provider_offer_id' => $data->providerOfferId,
            'destination_country' => $data->destinationCountry === null ? null : mb_strtoupper($data->destinationCountry),
        ]);

        return $data->manualNet ?? throw QuoteRuleViolation::manualNetRequired();
    }

}
