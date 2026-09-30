<x-layouts.guest :heading="__('identity.auth.confirm_password_title')" :title="__('identity.auth.confirm_password_title')">
    <p class="mb-md text-text-subtle">{{ __('identity.auth.confirm_password_help') }}</p>
    <form method="POST" action="{{ route('password.confirm') }}" class="flex flex-col gap-md" novalidate>
        @csrf
        <x-ui.field :label="__('identity.fields.password')" for="password">
            <x-ui.input name="password" type="password" autocomplete="current-password" required autofocus />
        </x-ui.field>
        <div class="flex flex-col-reverse gap-sm md:flex-row md:justify-end">
            @if ($mustEnableTwoFactor)
                <x-ui.button type="submit" form="logout-form" variant="secondary">{{ __('identity.auth.logout') }}</x-ui.button>
            @else
                <x-ui.link-button :href="$cancelUrl" variant="secondary">{{ __('shared.cancel') }}</x-ui.link-button>
            @endif
            <x-ui.button type="submit">{{ __('identity.auth.confirm') }}</x-ui.button>
        </div>
    </form>
    @if ($mustEnableTwoFactor)
        <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">
            @csrf
        </form>
        <p class="mt-md text-caption text-text-subtle">{{ __('identity.security.required_notice') }}</p>
    @endif
</x-layouts.guest>
