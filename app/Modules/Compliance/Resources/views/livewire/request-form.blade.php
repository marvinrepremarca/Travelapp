<form wire:submit="save" class="flex flex-col gap-lg" novalidate>
    <x-ui.card>
        <p class="mb-md text-caption text-text-subtle">{{ __('compliance.requests.legal_hint', ['days' => config('travel.compliance.request_business_days')]) }}</p>
        <div class="grid gap-md md:grid-cols-2">
            <x-ui.field :label="__('compliance.requests.fields.type')" for="type"><x-ui.select name="type" wire:model="type" :options="$types" required /></x-ui.field>
            <x-ui.field :label="__('compliance.requests.fields.channel')" for="channel"><x-ui.select name="channel" wire:model="channel" :options="$channels" required /></x-ui.field>
            <x-ui.field :label="__('compliance.requests.fields.requester_name')" for="requester_name"><x-ui.input name="requester_name" wire:model="requester_name" autocomplete="off" required /></x-ui.field>
            <x-ui.field :label="__('compliance.requests.fields.document_number')" for="document_number"><x-ui.input name="document_number" wire:model="document_number" autocomplete="off" required /></x-ui.field>
            <x-ui.field :label="__('compliance.requests.fields.email')" for="email"><x-ui.input name="email" type="email" wire:model="email" autocomplete="off" /></x-ui.field>
            <x-ui.field :label="__('compliance.requests.fields.phone')" for="phone"><x-ui.input name="phone" type="tel" wire:model="phone" autocomplete="off" /></x-ui.field>
            <x-ui.field :label="__('compliance.requests.fields.received_on')" for="received_on"><x-ui.input name="received_on" type="date" wire:model="received_on" required /></x-ui.field>
            <div class="md:col-span-2"><x-ui.field :label="__('compliance.requests.fields.details')" for="details"><x-ui.input name="details" wire:model="details" required /></x-ui.field></div>
        </div>
    </x-ui.card>
    <div class="flex flex-wrap gap-sm">
        <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">{{ __('compliance.requests.register') }}</x-ui.button>
        <x-ui.link-button variant="secondary" :href="route('compliance.requests')">{{ __('shared.cancel') }}</x-ui.link-button>
    </div>
</form>
