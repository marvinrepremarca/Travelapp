@php
    use App\Modules\Shared\Enums\Tone;
@endphp
<form wire:submit="add" class="flex flex-col gap-lg" novalidate>
    @if (session('status'))<x-ui.alert :tone="Tone::Success" role="status">{{ session('status') }}</x-ui.alert>@endif
    <p class="text-text-subtle">{{ __('bookings.direct.add_item_intro', ['customer' => $booking->customer->display_name]) }}</p>

    <div class="grid gap-md md:grid-cols-3">
        <x-ui.field :label="__('bookings.direct.item_fields.item.description')" for="item.description" class="md:col-span-2">
            <x-ui.input name="item.description" wire:model="item.description" />
        </x-ui.field>
        <x-ui.field :label="__('bookings.direct.item_fields.item.product_type')" for="item.product_type">
            <x-ui.select name="item.product_type" wire:model="item.product_type" :placeholder="__('bookings.direct.choose')"
                :options="collect($productTypes)->mapWithKeys(fn ($type) => [$type->value => $type->label()])->all()" />
        </x-ui.field>
        <x-ui.field :label="__('bookings.direct.item_fields.item.supplier')" for="item.supplier">
            <x-ui.select name="item.supplier" wire:model="item.supplier" :options="$suppliers" :placeholder="__('bookings.direct.no_supplier')" />
        </x-ui.field>
        <x-ui.field :label="__('bookings.direct.item_fields.item.destination_country')" for="item.destination_country" :hint="__('bookings.direct.country_hint')">
            <x-ui.input name="item.destination_country" wire:model="item.destination_country" maxlength="2" hint />
        </x-ui.field>
        <x-ui.field :label="__('bookings.direct.item_fields.item.channel')" for="item.channel">
            <x-ui.select name="item.channel" wire:model="item.channel"
                :options="collect($channels)->mapWithKeys(fn ($channel) => [$channel->value => $channel->label()])->all()" />
        </x-ui.field>
        <x-ui.field :label="__('bookings.direct.item_fields.item.service_date')" for="item.service_date">
            <x-ui.input name="item.service_date" type="date" wire:model="item.service_date" />
        </x-ui.field>
        <x-ui.field :label="__('bookings.direct.item_fields.item.nights')" for="item.nights">
            <x-ui.input name="item.nights" type="number" min="0" wire:model="item.nights" />
        </x-ui.field>
        <x-ui.field :label="__('bookings.direct.item_fields.item.ages')" for="item.ages" :hint="__('bookings.direct.ages_hint')">
            <x-ui.input name="item.ages" wire:model="item.ages" hint />
        </x-ui.field>
        <x-ui.field :label="__('bookings.direct.item_fields.item.net_amount')" for="item.net_amount" :hint="__('bookings.direct.net_hint')">
            <x-ui.input name="item.net_amount" type="number" min="0" step="any" inputmode="decimal" wire:model="item.net_amount" hint />
        </x-ui.field>
        <x-ui.field :label="__('bookings.direct.item_fields.item.net_currency')" for="item.net_currency">
            <x-ui.input name="item.net_currency" wire:model="item.net_currency" maxlength="3" />
        </x-ui.field>
    </div>

    <div class="flex flex-wrap gap-sm">
        <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="add">{{ __('bookings.direct.add_item') }}</x-ui.button>
        <x-ui.link-button variant="secondary" :href="route('bookings.show', $booking)">{{ __('bookings.direct.back_to_booking') }}</x-ui.link-button>
    </div>
</form>
