<x-layouts.guest :heading="__('identity.auth.confirm_password_title')" :title="__('identity.auth.confirm_password_title')">
    <p class="mb-md text-text-subtle">{{ __('identity.auth.confirm_password_help') }}</p>
    <form method="POST" action="{{ route('password.confirm') }}" class="flex flex-col gap-md" novalidate>
        @csrf
        <x-ui.field :label="__('identity.fields.password')" for="password">
            <x-ui.input name="password" type="password" autocomplete="current-password" required autofocus />
        </x-ui.field>
        <x-ui.button type="submit">{{ __('identity.auth.confirm') }}</x-ui.button>
    </form>
</x-layouts.guest>
