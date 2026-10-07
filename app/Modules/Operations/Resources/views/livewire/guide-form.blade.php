<form wire:submit="save" class="flex flex-col gap-lg" novalidate>
    <x-ui.card>
        <div class="grid gap-md md:grid-cols-2">
            <x-ui.field :label="__('operations.guides.fields.name')" for="name"><x-ui.input name="name" wire:model="name" required /></x-ui.field>
            <x-ui.field :label="__('operations.guides.fields.phone')" for="phone"><x-ui.input name="phone" type="tel" wire:model="phone" required /></x-ui.field>
            <x-ui.field :label="__('operations.guides.fields.languages')" for="languages" :hint="__('operations.guides.languages_hint')"><x-ui.input name="languages" wire:model="languages" hint /></x-ui.field>
            <x-ui.field :label="__('operations.guides.fields.license_number')" for="license_number" :hint="__('operations.guides.license_hint')"><x-ui.input name="license_number" wire:model="license_number" hint /></x-ui.field>
            <label class="flex items-center gap-sm text-body"><input type="checkbox" wire:model="is_active" class="rounded-control border-border"> {{ __('operations.guides.fields.is_active') }}</label>
        </div>
    </x-ui.card>
    <div class="flex flex-wrap gap-sm">
        <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">{{ __('shared.save') }}</x-ui.button>
        <x-ui.link-button variant="secondary" :href="route('operations.resources')">{{ __('shared.cancel') }}</x-ui.link-button>
    </div>
</form>
