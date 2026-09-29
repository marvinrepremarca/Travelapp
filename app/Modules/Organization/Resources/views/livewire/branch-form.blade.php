<form wire:submit="save" class="flex flex-col gap-lg" novalidate>
    <x-ui.card>
        <div class="grid gap-md md:grid-cols-2">
            <x-ui.field :label="__('organization.branches.fields.code')" for="code" :hint="__('organization.branches.code_hint')">
                <x-ui.input name="code" wire:model="code" required hint />
            </x-ui.field>
            <x-ui.field :label="__('organization.branches.fields.name')" for="name">
                <x-ui.input name="name" wire:model="name" required />
            </x-ui.field>
            <x-ui.field :label="__('organization.branches.fields.city')" for="city">
                <x-ui.input name="city" wire:model="city" />
            </x-ui.field>
            <x-ui.field :label="__('organization.branches.fields.address')" for="address">
                <x-ui.input name="address" wire:model="address" autocomplete="street-address" />
            </x-ui.field>
            <x-ui.field :label="__('organization.branches.fields.phone')" for="phone">
                <x-ui.input name="phone" type="tel" wire:model="phone" />
            </x-ui.field>
            <x-ui.field :label="__('organization.branches.fields.email')" for="email">
                <x-ui.input name="email" type="email" wire:model="email" />
            </x-ui.field>
            <x-ui.field :label="__('organization.branches.fields.timezone')" for="timezone">
                <x-ui.select name="timezone" wire:model="timezone" :options="array_combine($timezones, $timezones)" required />
            </x-ui.field>
            <x-ui.field :label="__('organization.branches.fields.manager_id')" for="manager_id" :hint="__('organization.branches.manager_hint')">
                <x-ui.select name="manager_id" wire:model="manager_id" :options="$managers" :placeholder="__('organization.branches.no_manager')" hint />
            </x-ui.field>
        </div>
    </x-ui.card>

    <div class="flex justify-end gap-sm">
        <x-ui.link-button :href="route('organization.branches.index')" variant="secondary">{{ __('shared.cancel') }}</x-ui.link-button>
        <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">
            <span wire:loading.remove wire:target="save">{{ __('shared.save') }}</span>
            <span wire:loading wire:target="save">{{ __('shared.saving') }}</span>
        </x-ui.button>
    </div>
</form>
