@php
    use App\Modules\Quotes\Enums\QuoteItemKind;
    $isCatalog = ($item['kind'] ?? '') === QuoteItemKind::Catalog->value;
@endphp
<form wire:submit="addItem" class="grid gap-md border-t border-border pt-md md:grid-cols-3 md:items-end" novalidate>
    <x-ui.field :label="__('quotes.item_fields.kind')" for="item.kind">
        <x-ui.select name="item.kind" wire:model.live="item.kind" :options="collect($kinds)->mapWithKeys(fn ($kind) => [$kind->value => $kind->label()])->all()" />
    </x-ui.field>
    @if ($isCatalog)
        <div class="md:col-span-2">
            <x-ui.field :label="__('quotes.item_fields.catalog_product')" for="item.catalog_product">
                <x-ui.select name="item.catalog_product" wire:model="item.catalog_product" :options="$catalogProducts" :placeholder="__('quotes.choose')" />
            </x-ui.field>
        </div>
    @else
        <x-ui.field :label="__('quotes.item_fields.product_type')" for="item.product_type">
            <x-ui.select name="item.product_type" wire:model="item.product_type" :placeholder="__('quotes.choose')"
                :options="collect($productTypes)->mapWithKeys(fn ($type) => [$type->value => $type->label()])->all()" />
        </x-ui.field>
        <x-ui.field :label="__('quotes.item_fields.description')" for="item.description"><x-ui.input name="item.description" wire:model="item.description" /></x-ui.field>
        <x-ui.field :label="__('quotes.item_fields.supplier_id')" for="item.supplier_id">
            <x-ui.select name="item.supplier_id" wire:model="item.supplier_id" :options="$suppliers" :placeholder="__('quotes.choose')" />
        </x-ui.field>
        <x-ui.field :label="__('quotes.item_fields.net_amount')" for="item.net_amount" :hint="__('quotes.net_hint')">
            <x-ui.input name="item.net_amount" type="number" min="0" step="any" wire:model="item.net_amount" hint />
        </x-ui.field>
        <x-ui.field :label="__('quotes.item_fields.net_currency')" for="item.net_currency"><x-ui.input name="item.net_currency" wire:model="item.net_currency" maxlength="3" /></x-ui.field>
        <x-ui.field :label="__('quotes.item_fields.destination_country')" for="item.destination_country"><x-ui.input name="item.destination_country" wire:model="item.destination_country" maxlength="2" /></x-ui.field>
    @endif
    <x-ui.field :label="__('quotes.item_fields.service_date')" for="item.service_date"><x-ui.input name="item.service_date" type="date" wire:model="item.service_date" /></x-ui.field>
    <x-ui.field :label="__('quotes.item_fields.nights')" for="item.nights"><x-ui.input name="item.nights" type="number" min="0" wire:model="item.nights" /></x-ui.field>
    <x-ui.field :label="__('quotes.item_fields.ages')" for="item.ages" :hint="__('quotes.ages_hint')"><x-ui.input name="item.ages" wire:model="item.ages" hint /></x-ui.field>
    <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="addItem">{{ __('quotes.items.add') }}</x-ui.button>
</form>
