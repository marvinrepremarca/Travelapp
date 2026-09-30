<form wire:submit="save" class="flex max-w-page flex-col gap-lg" novalidate>
    <p class="text-text-subtle">{{ __('crm.travelers.for_customer', ['name' => $customer->display_name]) }}</p>

    <x-ui.card :title="__('crm.travelers.identity_section')">
        <div class="grid gap-md md:grid-cols-2">
            <x-ui.field :label="__('crm.travelers.fields.first_name')" for="first_name" :hint="__('crm.travelers.name_hint')">
                <x-ui.input name="first_name" wire:model="first_name" autocomplete="off" hint required />
            </x-ui.field>
            <x-ui.field :label="__('crm.travelers.fields.last_name')" for="last_name">
                <x-ui.input name="last_name" wire:model="last_name" autocomplete="off" required />
            </x-ui.field>
            <x-ui.field :label="__('crm.travelers.fields.gender')" for="gender">
                <x-ui.select name="gender" wire:model="gender" :placeholder="__('crm.travelers.choose_gender')"
                    :options="collect($genders)->mapWithKeys(fn ($gender) => [$gender->value => $gender->label()])->all()" />
            </x-ui.field>
            <x-ui.field :label="__('crm.travelers.fields.birth_date')" for="birth_date"
                :hint="$isEditing ? __('crm.customers.birth_date_edit_hint') : null">
                <x-ui.input name="birth_date" type="date" wire:model="birth_date" :hint="$isEditing" :required="! $isEditing" />
            </x-ui.field>
            <x-ui.field :label="__('crm.travelers.fields.nationality')" for="nationality" :hint="__('crm.customers.country_hint')">
                <x-ui.input name="nationality" wire:model="nationality" maxlength="2" hint required />
            </x-ui.field>
        </div>
    </x-ui.card>

    <x-ui.card :title="__('crm.travelers.passport_section')">
        <div class="grid gap-md md:grid-cols-3">
            <x-ui.field :label="__('crm.travelers.fields.passport_number')" for="passport_number"
                :hint="$isEditing ? __('crm.customers.document_edit_hint') : __('crm.customers.document_hint')">
                <x-ui.input name="passport_number" wire:model="passport_number" autocomplete="off" hint />
            </x-ui.field>
            <x-ui.field :label="__('crm.travelers.fields.passport_country')" for="passport_country">
                <x-ui.input name="passport_country" wire:model="passport_country" maxlength="2" />
            </x-ui.field>
            <x-ui.field :label="__('crm.travelers.fields.passport_expires_on')" for="passport_expires_on">
                <x-ui.input name="passport_expires_on" type="date" wire:model="passport_expires_on" />
            </x-ui.field>
        </div>
    </x-ui.card>

    <div class="flex justify-end gap-sm">
        <x-ui.link-button :href="route('crm.customers.show', $customer)" variant="secondary">{{ __('shared.cancel') }}</x-ui.link-button>
        <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">
            <span wire:loading.remove wire:target="save">{{ __('shared.save') }}</span>
            <span wire:loading wire:target="save">{{ __('shared.saving') }}</span>
        </x-ui.button>
    </div>
</form>
