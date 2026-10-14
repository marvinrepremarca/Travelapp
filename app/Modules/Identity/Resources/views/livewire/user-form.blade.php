@php
    use App\Modules\Shared\Enums\Tone;
@endphp
<div>
@if ($issuedPassword !== null)
    <x-ui.card :title="__('identity.users.temporary_password_title', ['name' => $issuedUserName])">
        <div class="flex flex-col gap-md" x-data>
            <x-ui.alert>{{ __('identity.users.temporary_password_once') }}</x-ui.alert>
            <x-ui.field :label="__('identity.users.temporary_password_label')" for="issued-password">
                <x-ui.input name="issued-password" x-ref="password" value="{{ $issuedPassword }}" readonly autocomplete="off" />
            </x-ui.field>
            <div class="flex flex-wrap justify-end gap-sm">
                <x-ui.copy-button :text="$issuedPassword" :label="__('identity.users.copy_password')" />
                <x-ui.button type="button" wire:click="finishIssuing">{{ __('identity.users.temporary_password_done') }}</x-ui.button>
            </div>
        </div>
    </x-ui.card>
@else
<form wire:submit="save" class="flex max-w-form flex-col gap-lg" novalidate>
    @if ($errors->any())
        <x-ui.alert :tone="Tone::Danger" role="alert">{{ __('identity.users.form_has_errors') }}</x-ui.alert>
    @endif
    <x-ui.card>
        <div class="flex flex-col gap-md">
            @unless ($isEditing)
                <label class="flex items-center gap-sm text-body">
                    <input type="checkbox" wire:model.live="withTemporaryPassword" class="rounded-control border-border">
                    {{ __('identity.users.with_temporary_password') }}
                </label>
                <x-ui.alert>{{ $withTemporaryPassword ? __('identity.users.temporary_password_help') : __('identity.users.invitation_help') }}</x-ui.alert>
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
            <span wire:loading.remove wire:target="save">{{ $isEditing ? __('shared.save') : ($withTemporaryPassword ? __('identity.users.create_with_temporary_password') : __('identity.users.send_invitation')) }}</span>
            <span wire:loading wire:target="save">{{ __('shared.saving') }}</span>
        </x-ui.button>
    </div>
</form>
@endif
</div>
