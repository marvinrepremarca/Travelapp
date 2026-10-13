<form wire:submit="save" class="flex max-w-form flex-col gap-lg" novalidate>
    <x-ui.card>
        <div class="flex flex-col gap-md">
            <x-ui.alert>{{ __('identity.password_change.intro') }}</x-ui.alert>
            <x-ui.field :label="__('identity.password_change.password')" for="password" :hint="__('identity.password_change.policy')">
                <x-ui.input name="password" type="password" wire:model="password" autocomplete="new-password" hint required />
            </x-ui.field>
            <x-ui.field :label="__('identity.password_change.confirm')" for="password_confirmation">
                <x-ui.input name="password_confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password" required />
            </x-ui.field>
        </div>
    </x-ui.card>

    <div class="flex justify-end">
        <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">
            <span wire:loading.remove wire:target="save">{{ __('identity.password_change.submit') }}</span>
            <span wire:loading wire:target="save">{{ __('shared.saving') }}</span>
        </x-ui.button>
    </div>
</form>
