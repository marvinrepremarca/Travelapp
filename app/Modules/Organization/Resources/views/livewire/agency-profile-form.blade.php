<div class="flex flex-col gap-lg">
    @if ($rntWarning)
        <x-ui.alert :tone="\App\Modules\Shared\Enums\Tone::Warning">{{ __('organization.agency.rnt_expiring') }}</x-ui.alert>
    @endif

    <form wire:submit="save" class="flex flex-col gap-lg" novalidate>
        <x-ui.card :title="__('organization.agency.legal_section')">
            <div class="grid gap-md md:grid-cols-2">
                <x-ui.field :label="__('organization.agency.fields.legal_name')" for="legal_name">
                    <x-ui.input name="legal_name" wire:model="legal_name" required />
                </x-ui.field>
                <x-ui.field :label="__('organization.agency.fields.trade_name')" for="trade_name">
                    <x-ui.input name="trade_name" wire:model="trade_name" required />
                </x-ui.field>
                <div class="flex gap-sm">
                    <x-ui.field :label="__('organization.agency.fields.nit')" for="nit" class="flex-1">
                        <x-ui.input name="nit" wire:model="nit" inputmode="numeric" required />
                    </x-ui.field>
                    <x-ui.field :label="__('organization.agency.fields.nit_check_digit')" for="nit_check_digit">
                        <x-ui.input name="nit_check_digit" wire:model="nit_check_digit" inputmode="numeric" maxlength="1" required />
                    </x-ui.field>
                </div>
                <div class="flex gap-sm">
                    <x-ui.field :label="__('organization.agency.fields.rnt_number')" for="rnt_number" class="flex-1">
                        <x-ui.input name="rnt_number" wire:model="rnt_number" required />
                    </x-ui.field>
                    <x-ui.field :label="__('organization.agency.fields.rnt_expires_on')" for="rnt_expires_on" class="flex-1">
                        <x-ui.input name="rnt_expires_on" type="date" wire:model.live.debounce.500ms="rnt_expires_on" required />
                    </x-ui.field>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card :title="__('organization.agency.contact_section')">
            <div class="grid gap-md md:grid-cols-2">
                <x-ui.field :label="__('organization.agency.fields.address')" for="address">
                    <x-ui.input name="address" wire:model="address" autocomplete="street-address" required />
                </x-ui.field>
                <x-ui.field :label="__('organization.agency.fields.city')" for="city">
                    <x-ui.input name="city" wire:model="city" required />
                </x-ui.field>
                <x-ui.field :label="__('organization.agency.fields.phone')" for="phone">
                    <x-ui.input name="phone" type="tel" wire:model="phone" autocomplete="tel" required />
                </x-ui.field>
                <x-ui.field :label="__('organization.agency.fields.email')" for="email">
                    <x-ui.input name="email" type="email" wire:model="email" autocomplete="email" required />
                </x-ui.field>
                <x-ui.field :label="__('organization.agency.fields.website')" for="website" :hint="__('organization.agency.website_hint')">
                    <x-ui.input name="website" type="url" wire:model="website" hint />
                </x-ui.field>
            </div>
        </x-ui.card>

        <x-ui.card :title="__('organization.agency.brand_section')">
            <div class="grid gap-md md:grid-cols-2">
                <x-ui.field :label="__('organization.agency.fields.brand_primary_color')" for="brand_primary_color" :hint="__('organization.agency.color_hint')">
                    <x-ui.input name="brand_primary_color" wire:model="brand_primary_color" hint />
                </x-ui.field>
                <x-ui.field :label="__('organization.agency.fields.brand_accent_color')" for="brand_accent_color" :hint="__('organization.agency.color_hint')">
                    <x-ui.input name="brand_accent_color" wire:model="brand_accent_color" hint />
                </x-ui.field>
                <x-ui.field :label="__('organization.agency.fields.logo')" for="logo" :hint="__('organization.agency.logo_hint', ['kb' => config('travel.organization.logo_max_kb')])">
                    <x-ui.input name="logo" type="file" wire:model="logo" accept="image/png,image/jpeg,image/webp" hint />
                </x-ui.field>
                @if ($currentLogoUrl)
                    <img src="{{ $currentLogoUrl }}" alt="{{ __('organization.agency.logo_alt', ['name' => $trade_name]) }}" class="h-2xl w-auto">
                @endif
            </div>
        </x-ui.card>

        <div class="flex justify-end">
            <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save,logo">
                <span wire:loading.remove wire:target="save">{{ __('shared.save') }}</span>
                <span wire:loading wire:target="save">{{ __('shared.saving') }}</span>
            </x-ui.button>
        </div>
    </form>
</div>
