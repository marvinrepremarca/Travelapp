<form wire:submit="save" class="flex max-w-page flex-col gap-lg" novalidate>
    <x-ui.customer-picker :title="__('quotes.fields.customer_ulid')" error="customer_ulid" :selected="$selected" :matches="$matches" :search="$customerSearch" />

    <x-ui.card>
        <div class="grid gap-md md:grid-cols-3">
            <div class="md:col-span-3">
                <x-ui.field :label="__('quotes.fields.title')" for="title">
                    <x-ui.input name="title" wire:model="title" required />
                </x-ui.field>
            </div>
            <x-ui.field :label="__('quotes.fields.sale_currency')" for="sale_currency">
                <x-ui.currency-select name="sale_currency" wire:model="sale_currency" required />
            </x-ui.field>
            <x-ui.field :label="__('quotes.fields.sales_channel')" for="sales_channel">
                <x-ui.select name="sales_channel" wire:model="sales_channel" required
                    :options="collect($channels)->mapWithKeys(fn ($channel) => [$channel->value => $channel->label()])->all()" />
            </x-ui.field>
        </div>
    </x-ui.card>

    <div class="flex gap-sm">
        <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">{{ __('quotes.create') }}</x-ui.button>
        <x-ui.link-button variant="secondary" :href="route('quotes.index')">{{ __('shared.cancel') }}</x-ui.link-button>
    </div>
</form>
