<form wire:submit="save" class="flex max-w-page flex-col gap-lg" novalidate>
    <x-ui.card :title="__('suppliers.legal_section')">
        <div class="grid gap-md md:grid-cols-2">
            <x-ui.field :label="__('suppliers.fields.legal_name')" for="legal_name">
                <x-ui.input name="legal_name" wire:model="legal_name" required />
            </x-ui.field>
            <x-ui.field :label="__('suppliers.fields.trade_name')" for="trade_name">
                <x-ui.input name="trade_name" wire:model="trade_name" required />
            </x-ui.field>
            <x-ui.field :label="__('suppliers.fields.tax_id')" for="tax_id">
                <x-ui.input name="tax_id" wire:model="tax_id" required />
            </x-ui.field>
            <x-ui.field :label="__('suppliers.fields.country')" for="country" :hint="__('crm.customers.country_hint')">
                <x-ui.input name="country" wire:model.live="country" maxlength="2" hint required />
            </x-ui.field>
            <label class="flex items-center gap-sm text-body md:col-span-2">
                <input type="checkbox" wire:model.live="is_tourism_provider" class="rounded-control border-border">
                {{ __('suppliers.fields.is_tourism_provider') }}
            </label>
            <x-ui.field :label="__('suppliers.fields.rnt_number')" for="rnt_number" :hint="__('suppliers.rnt_hint')">
                <x-ui.input name="rnt_number" wire:model="rnt_number" hint />
            </x-ui.field>
            <x-ui.field :label="__('suppliers.fields.rnt_expires_on')" for="rnt_expires_on">
                <x-ui.input name="rnt_expires_on" type="date" wire:model="rnt_expires_on" />
            </x-ui.field>
        </div>
    </x-ui.card>

    <x-ui.card :title="__('suppliers.payment_section')">
        <div class="grid gap-md md:grid-cols-3">
            <x-ui.field :label="__('suppliers.fields.payment_terms')" for="payment_terms" :hint="__('suppliers.terms_hint')">
                <x-ui.select name="payment_terms" wire:model="payment_terms" hint
                    :options="collect($terms)->mapWithKeys(fn ($term) => [$term->value => $term->label()])->all()" />
            </x-ui.field>
            <x-ui.field :label="__('suppliers.fields.payment_days')" for="payment_days">
                <x-ui.input name="payment_days" type="number" min="0" wire:model="payment_days" required />
            </x-ui.field>
            <x-ui.field :label="__('suppliers.fields.payment_currency')" for="payment_currency">
                <x-ui.input name="payment_currency" wire:model="payment_currency" maxlength="3" required />
            </x-ui.field>
        </div>
    </x-ui.card>

    <x-ui.card :title="__('suppliers.contact_section')">
        <div class="grid gap-md md:grid-cols-3">
            <x-ui.field :label="__('suppliers.fields.email')" for="email">
                <x-ui.input name="email" type="email" wire:model="email" />
            </x-ui.field>
            <x-ui.field :label="__('suppliers.fields.phone')" for="phone">
                <x-ui.input name="phone" type="tel" wire:model="phone" />
            </x-ui.field>
            <x-ui.field :label="__('suppliers.fields.website')" for="website">
                <x-ui.input name="website" type="url" wire:model="website" />
            </x-ui.field>
            <x-ui.field :label="__('suppliers.fields.notes')" for="notes" class="md:col-span-3">
                <x-ui.input name="notes" wire:model="notes" />
            </x-ui.field>
        </div>
    </x-ui.card>

    <div class="flex justify-end gap-sm">
        <x-ui.link-button :href="route('suppliers.index')" variant="secondary">{{ __('shared.cancel') }}</x-ui.link-button>
        <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">{{ __('shared.save') }}</x-ui.button>
    </div>
</form>
