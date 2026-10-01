<form wire:submit="save" class="flex max-w-page flex-col gap-lg" novalidate>
    <x-ui.card :title="__('catalog.general_section')">
        <div class="grid gap-md md:grid-cols-2">
            <x-ui.field :label="__('catalog.fields.code')" for="code">
                <x-ui.input name="code" wire:model="code" maxlength="30" required />
            </x-ui.field>
            <x-ui.field :label="__('catalog.fields.product_type')" for="product_type">
                <x-ui.select name="product_type" wire:model="product_type" :placeholder="__('catalog.choose_type')" required
                    :options="collect($types)->mapWithKeys(fn ($type) => [$type->value => $type->label()])->all()" />
            </x-ui.field>
            <x-ui.field :label="__('catalog.fields.name')" for="name">
                <x-ui.input name="name" wire:model="name" required />
            </x-ui.field>
            <x-ui.field :label="__('catalog.fields.duration_minutes')" for="duration_minutes">
                <x-ui.input name="duration_minutes" type="number" min="1" wire:model="duration_minutes" />
            </x-ui.field>
            <div class="md:col-span-2">
                <x-ui.field :label="__('catalog.fields.description')" for="description">
                    <x-ui.input name="description" wire:model="description" />
                </x-ui.field>
            </div>
        </div>
    </x-ui.card>

    <x-ui.card :title="__('catalog.operation_section')">
        <div class="grid gap-md md:grid-cols-3">
            <x-ui.field :label="__('catalog.fields.destination_country')" for="destination_country">
                <x-ui.input name="destination_country" wire:model="destination_country" maxlength="2" required />
            </x-ui.field>
            <x-ui.field :label="__('catalog.fields.destination_city')" for="destination_city">
                <x-ui.input name="destination_city" wire:model="destination_city" required />
            </x-ui.field>
            <x-ui.field :label="__('catalog.fields.timezone')" for="timezone" :hint="__('catalog.timezone_hint')">
                <x-ui.input name="timezone" wire:model="timezone" hint required />
            </x-ui.field>
            <x-ui.field :label="__('catalog.fields.supplier_id')" for="supplier_id">
                <x-ui.select name="supplier_id" wire:model="supplier_id" :options="$suppliers" :placeholder="__('catalog.no_supplier')" />
            </x-ui.field>
            <x-ui.field :label="__('catalog.fields.currency')" for="currency" :hint="__('catalog.currency_hint')">
                <x-ui.input name="currency" wire:model="currency" maxlength="3" hint required />
            </x-ui.field>
        </div>
    </x-ui.card>

    <div class="flex gap-sm">
        <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">{{ __('shared.save') }}</x-ui.button>
        <x-ui.link-button variant="secondary" :href="route('catalog.index')">{{ __('shared.cancel') }}</x-ui.link-button>
    </div>
</form>
