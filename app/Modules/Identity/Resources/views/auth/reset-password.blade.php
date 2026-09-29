<x-layouts.guest :heading="__('identity.auth.reset_password_title')" :title="__('identity.auth.reset_password_title')">
    <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-md" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <x-ui.field :label="__('identity.fields.email')" for="email">
            <x-ui.input name="email" type="email" :value="old('email', $request->string('email')->toString())" autocomplete="username" required />
        </x-ui.field>
        <x-ui.field :label="__('identity.fields.password')" for="password" :hint="__('identity.auth.password_rules')">
            <x-ui.input name="password" type="password" autocomplete="new-password" hint required />
        </x-ui.field>
        <x-ui.field :label="__('identity.fields.password_confirmation')" for="password_confirmation">
            <x-ui.input name="password_confirmation" type="password" autocomplete="new-password" required />
        </x-ui.field>
        <x-ui.button type="submit">{{ __('identity.auth.reset_password') }}</x-ui.button>
    </form>
</x-layouts.guest>
