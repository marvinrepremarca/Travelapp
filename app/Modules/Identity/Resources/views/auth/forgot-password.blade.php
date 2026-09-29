<x-layouts.guest :heading="__('identity.auth.forgot_password_title')" :title="__('identity.auth.forgot_password_title')">
    <p class="mb-md text-text-subtle">{{ __('identity.auth.forgot_password_help') }}</p>
    <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-md" novalidate>
        @csrf
        <x-ui.field :label="__('identity.fields.email')" for="email">
            <x-ui.input name="email" type="email" :value="old('email')" autocomplete="username" required autofocus />
        </x-ui.field>
        <x-ui.button type="submit">{{ __('identity.auth.send_reset_link') }}</x-ui.button>
        <a href="{{ route('login') }}" class="text-center text-body text-brand underline">{{ __('identity.auth.back_to_login') }}</a>
    </form>
</x-layouts.guest>
