<x-layouts.guest :heading="__('identity.auth.login_title')" :title="__('identity.auth.login_title')">
    <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-md" novalidate>
        @csrf
        <x-ui.field :label="__('identity.fields.email')" for="email">
            <x-ui.input name="email" type="email" :value="old('email')" autocomplete="username" required autofocus />
        </x-ui.field>
        <x-ui.field :label="__('identity.fields.password')" for="password">
            <x-ui.input name="password" type="password" autocomplete="current-password" required />
        </x-ui.field>
        <label class="flex items-center gap-sm text-body">
            <input type="checkbox" name="remember" class="rounded-control border-border">
            {{ __('identity.auth.remember_me') }}
        </label>
        <x-ui.button type="submit">{{ __('identity.auth.login') }}</x-ui.button>
        <a href="{{ route('password.request') }}" class="text-center text-body text-brand underline">{{ __('identity.auth.forgot_password') }}</a>
    </form>
</x-layouts.guest>
