<form wire:submit="save" class="flex max-w-page flex-col gap-lg" novalidate>
    <x-ui.card>
        <div class="grid gap-md md:grid-cols-2">
            <x-ui.field :label="__('crm.leads.fields.contact_name')" for="contact_name">
                <x-ui.input name="contact_name" wire:model="contact_name" required />
            </x-ui.field>
            <x-ui.field :label="__('crm.leads.fields.channel')" for="channel">
                <x-ui.select name="channel" wire:model="channel"
                    :options="collect($channels)->mapWithKeys(fn ($channel) => [$channel->value => $channel->label()])->all()" />
            </x-ui.field>
            <x-ui.field :label="__('crm.leads.fields.email')" for="email" :hint="__('crm.leads.contact_hint')">
                <x-ui.input name="email" type="email" wire:model="email" hint />
            </x-ui.field>
            <x-ui.field :label="__('crm.leads.fields.phone')" for="phone">
                <x-ui.input name="phone" type="tel" wire:model="phone" />
            </x-ui.field>
            <x-ui.field :label="__('crm.leads.fields.destination')" for="destination">
                <x-ui.input name="destination" wire:model="destination" />
            </x-ui.field>
            <x-ui.field :label="__('crm.leads.fields.travelers_count')" for="travelers_count">
                <x-ui.input name="travelers_count" type="number" min="1" wire:model="travelers_count" />
            </x-ui.field>
            <x-ui.field :label="__('crm.leads.fields.travel_start')" for="travel_start">
                <x-ui.input name="travel_start" type="date" wire:model="travel_start" />
            </x-ui.field>
            <x-ui.field :label="__('crm.leads.fields.travel_end')" for="travel_end">
                <x-ui.input name="travel_end" type="date" wire:model="travel_end" />
            </x-ui.field>
            <x-ui.field :label="__('crm.leads.fields.notes')" for="notes" class="md:col-span-2" :hint="__('crm.customers.notes_hint')">
                <x-ui.input name="notes" wire:model="notes" hint />
            </x-ui.field>
        </div>
    </x-ui.card>
    <div class="flex justify-end gap-sm">
        <x-ui.link-button :href="route('crm.leads.index')" variant="secondary">{{ __('shared.cancel') }}</x-ui.link-button>
        <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">{{ __('shared.save') }}</x-ui.button>
    </div>
</form>
