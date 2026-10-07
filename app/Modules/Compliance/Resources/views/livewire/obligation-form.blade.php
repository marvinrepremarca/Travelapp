<form wire:submit="save" class="flex flex-col gap-lg" novalidate>
    <x-ui.card>
        <div class="grid gap-md md:grid-cols-2">
            <div class="md:col-span-2"><x-ui.field :label="__('compliance.obligations.fields.title')" for="title" :hint="__('compliance.obligations.title_hint')"><x-ui.input name="title" wire:model="title" required hint /></x-ui.field></div>
            <x-ui.field :label="__('compliance.obligations.fields.due_on')" for="due_on"><x-ui.input name="due_on" type="date" wire:model="due_on" required /></x-ui.field>
            <x-ui.field :label="__('compliance.obligations.fields.recurrence')" for="recurrence"><x-ui.select name="recurrence" wire:model="recurrence" :options="$recurrences" required /></x-ui.field>
            <x-ui.field :label="__('compliance.obligations.fields.responsible_id')" for="responsible_id" :hint="__('compliance.obligations.responsible_hint', ['days' => config('travel.compliance.obligation_alert_days')])"><x-ui.select name="responsible_id" wire:model="responsible_id" :options="$responsibles" required hint /></x-ui.field>
            <x-ui.field :label="__('compliance.obligations.fields.description')" for="description"><x-ui.input name="description" wire:model="description" /></x-ui.field>
        </div>
    </x-ui.card>
    <div class="flex flex-wrap gap-sm">
        <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">{{ __('shared.save') }}</x-ui.button>
        <x-ui.link-button variant="secondary" :href="route('compliance.index')">{{ __('shared.cancel') }}</x-ui.link-button>
    </div>
</form>
