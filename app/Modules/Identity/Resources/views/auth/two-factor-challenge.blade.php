<x-layouts.guest :heading="__('identity.auth.two_factor_title')" :title="__('identity.auth.two_factor_title')">
    <p class="mb-md text-text-subtle">{{ __('identity.auth.two_factor_help') }}</p>
    <form method="POST" action="{{ route('two-factor.login') }}" class="flex flex-col gap-md" novalidate>
        @csrf
        <x-ui.field :label="__('identity.fields.two_factor_code')" for="code">
            <x-ui.input name="code" inputmode="numeric" autocomplete="one-time-code" autofocus />
        </x-ui.field>
        <x-ui.field :label="__('identity.fields.recovery_code')" for="recovery_code">
            <x-ui.input name="recovery_code" autocomplete="off" />
        </x-ui.field>
        <x-ui.button type="submit">{{ __('identity.auth.verify') }}</x-ui.button>
        <a href="{{ route('login') }}" class="text-center text-body text-brand underline">{{ __('identity.auth.back_to_login') }}</a>
    </form>
</x-layouts.guest>
