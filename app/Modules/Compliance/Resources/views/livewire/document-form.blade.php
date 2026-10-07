<form wire:submit="save" class="flex flex-col gap-lg" novalidate>
    <x-ui.card>
        <div class="grid gap-md md:grid-cols-2">
            <x-ui.field :label="__('compliance.documents.fields.type')" for="type"><x-ui.select name="type" wire:model="type" :options="$types" required /></x-ui.field>
            <x-ui.field :label="__('compliance.documents.fields.number')" for="number"><x-ui.input name="number" wire:model="number" required /></x-ui.field>
            <x-ui.field :label="__('compliance.documents.fields.issuer')" for="issuer"><x-ui.input name="issuer" wire:model="issuer" /></x-ui.field>
            <x-ui.field :label="__('compliance.documents.fields.responsible_id')" for="responsible_id"><x-ui.select name="responsible_id" wire:model="responsible_id" :options="$responsibles" required /></x-ui.field>
            <x-ui.field :label="__('compliance.documents.fields.starts_on')" for="starts_on"><x-ui.input name="starts_on" type="date" wire:model="starts_on" /></x-ui.field>
            <x-ui.field :label="__('compliance.documents.fields.expires_on')" for="expires_on" :hint="__('compliance.documents.expires_hint', ['days' => config('travel.compliance.document_alert_days')])"><x-ui.input name="expires_on" type="date" wire:model="expires_on" required hint /></x-ui.field>
            <div class="md:col-span-2"><x-ui.field :label="__('compliance.documents.fields.notes')" for="notes"><x-ui.input name="notes" wire:model="notes" /></x-ui.field></div>
        </div>
    </x-ui.card>
    <div class="flex flex-wrap gap-sm">
        <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">{{ __('shared.save') }}</x-ui.button>
        <x-ui.link-button variant="secondary" :href="route('compliance.documents')">{{ __('shared.cancel') }}</x-ui.link-button>
    </div>
</form>
