<form wire:submit="save" class="flex max-w-form flex-col gap-lg" novalidate>
    <x-ui.card>
        <div class="flex flex-col gap-md">
            @unless ($isEditing)
                <x-ui.alert>{{ __('identity.users.invitation_help') }}</x-ui.alert>
            @endunless
            <x-ui.field :label="__('identity.users.fields.name')" for="name">
                <x-ui.input name="name" wire:model="name" autocomplete="off" required />
            </x-ui.field>
            <x-ui.field :label="__('identity.users.fields.email')" for="email">
                <x-ui.input name="email" type="email" wire:model="email" autocomplete="off" required />
            </x-ui.field>
            <x-ui.field :label="__('identity.users.fields.role')" for="role" :hint="__('identity.users.role_hint')">
                <x-ui.select name="role" wire:model.live="role" :options="$roles" :placeholder="__('identity.users.choose_role')" hint required />
            </x-ui.field>
            <x-ui.field :label="__('identity.users.fields.branch_id')" for="branch_id">
                <x-ui.select name="branch_id" wire:model="branch_id" :options="$branches" :placeholder="__('identity.users.no_branch')" />
            </x-ui.field>
            <x-ui.field :label="__('identity.users.fields.visibility_scope')" for="visibility_scope" :hint="__('identity.users.scope_hint')">
                <x-ui.select name="visibility_scope" wire:model="visibility_scope" :options="$scopes" :placeholder="__('identity.users.choose_scope')" hint required />
            </x-ui.field>
        </div>
    </x-ui.card>

    <div class="flex justify-end gap-sm">
        <x-ui.link-button :href="route('identity.users.index')" variant="secondary">{{ __('shared.cancel') }}</x-ui.link-button>
        <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">
            <span wire:loading.remove wire:target="save">{{ $isEditing ? __('shared.save') : __('identity.users.send_invitation') }}</span>
            <span wire:loading wire:target="save">{{ __('shared.saving') }}</span>
        </x-ui.button>
    </div>
</form>
