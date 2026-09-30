@php
    use App\Modules\Shared\Enums\Tone;
@endphp
<div class="flex max-w-form flex-col gap-lg">
    @error('two_factor')
        <x-ui.alert :tone="Tone::Danger">{{ $message }}</x-ui.alert>
    @enderror
    @if ($required && ! $enabled)
        <x-ui.alert :tone="Tone::Warning">{{ __('identity.security.required_notice') }}</x-ui.alert>
    @endif

    <x-ui.card :title="__('identity.security.two_factor_title')">
        <div class="flex flex-col gap-md">
            <p class="text-text-subtle">{{ __('identity.security.two_factor_help') }}</p>

            @if ($enabled)
                <x-ui.badge :tone="Tone::Success">{{ __('identity.users.two_factor_on') }}</x-ui.badge>

                @if ($recoveryCodes !== [])
                    <div class="flex flex-col gap-sm">
                        <p class="font-medium">{{ __('identity.security.recovery_codes_title') }}</p>
                        <p class="text-caption text-text-subtle">{{ __('identity.security.recovery_codes_help') }}</p>
                        <ul class="grid grid-cols-2 gap-xs rounded-control bg-muted p-md font-mono text-caption">
                            @foreach ($recoveryCodes as $recoveryCode)
                                <li wire:key="recovery-{{ $loop->index }}">{{ $recoveryCode }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="flex flex-wrap gap-sm">
                    <x-ui.button variant="secondary" wire:click="regenerateRecoveryCodes" wire:loading.attr="disabled">{{ __('identity.security.regenerate_codes') }}</x-ui.button>
                    @unless ($required)
                        <x-ui.button variant="danger" wire:click="disable" wire:loading.attr="disabled"
                            wire:confirm="{{ __('identity.security.confirm_disable') }}">{{ __('identity.security.disable') }}</x-ui.button>
                    @endunless
                </div>
            @elseif ($pending)
                <p>{{ __('identity.security.scan_help') }}</p>
                <img src="data:image/svg+xml;base64,{{ $qrCode }}" alt="{{ __('identity.security.qr_alt') }}" class="h-2xl w-auto self-start bg-surface p-sm">
                <form wire:submit="confirm" class="flex flex-col gap-md" novalidate>
                    <x-ui.field :label="__('identity.fields.two_factor_code')" for="code">
                        <x-ui.input name="code" wire:model="code" inputmode="numeric" autocomplete="one-time-code" required />
                    </x-ui.field>
                    <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="confirm">{{ __('identity.auth.verify') }}</x-ui.button>
                </form>
            @else
                <x-ui.button wire:click="enable" wire:loading.attr="disabled">{{ __('identity.security.enable') }}</x-ui.button>
            @endif
        </div>
    </x-ui.card>

    <x-ui.card :title="__('identity.security.password_title')">
        <form method="POST" action="{{ route('user-password.update') }}" class="flex flex-col gap-md" novalidate>
            @csrf
            @method('PUT')
            <x-ui.field :label="__('identity.security.current_password')" for="current_password" :error="$errors->updatePassword->first('current_password')">
                <x-ui.input name="current_password" type="password" autocomplete="current-password" required />
            </x-ui.field>
            <x-ui.field :label="__('identity.fields.password')" for="password" :hint="__('identity.auth.password_rules')" :error="$errors->updatePassword->first('password')">
                <x-ui.input name="password" type="password" autocomplete="new-password" hint required />
            </x-ui.field>
            <x-ui.field :label="__('identity.fields.password_confirmation')" for="password_confirmation">
                <x-ui.input name="password_confirmation" type="password" autocomplete="new-password" required />
            </x-ui.field>
            <x-ui.button type="submit">{{ __('identity.security.change_password') }}</x-ui.button>
        </form>
    </x-ui.card>
</div>
