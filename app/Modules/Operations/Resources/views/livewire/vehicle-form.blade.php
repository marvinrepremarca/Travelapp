<form wire:submit="save" class="flex flex-col gap-lg" novalidate>
    <x-ui.card>
        <div class="grid gap-md md:grid-cols-2">
            <x-ui.field :label="__('operations.vehicles.fields.plate')" for="plate" :hint="__('operations.vehicles.plate_hint')"><x-ui.input name="plate" wire:model="plate" required hint /></x-ui.field>
            <x-ui.field :label="__('operations.vehicles.fields.description')" for="description" :hint="__('operations.vehicles.description_hint')"><x-ui.input name="description" wire:model="description" required hint /></x-ui.field>
            <x-ui.field :label="__('operations.vehicles.fields.capacity')" for="capacity"><x-ui.input name="capacity" type="number" min="1" wire:model="capacity" required /></x-ui.field>
            <label class="flex items-center gap-sm text-body"><input type="checkbox" wire:model="is_active" class="rounded-control border-border"> {{ __('operations.vehicles.fields.is_active') }}</label>
        </div>
    </x-ui.card>
    <div class="flex flex-wrap gap-sm">
        <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">{{ __('shared.save') }}</x-ui.button>
        <x-ui.link-button variant="secondary" :href="route('operations.resources')">{{ __('shared.cancel') }}</x-ui.link-button>
    </div>
</form>
