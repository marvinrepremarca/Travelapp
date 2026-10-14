<form wire:submit="save" class="flex flex-col gap-lg" novalidate>
    <p class="text-text-subtle">{{ __('bookings.direct.intro') }}</p>

    <x-ui.customer-picker :title="__('bookings.direct.fields.customerUlid')" error="customerUlid" :selected="$selected" :matches="$matches" :search="$customerSearch" />

    <x-ui.field :label="__('bookings.direct.fields.title')" for="title">
        <x-ui.input name="title" wire:model="title" />
    </x-ui.field>

    <div>
        <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">{{ __('bookings.direct.submit') }}</x-ui.button>
    </div>
</form>
