<div class="flex flex-col gap-md md:flex-row md:items-end md:justify-between">
    <x-ui.field :label="__('reports.month')" for="month">
        <x-ui.input name="month" type="month" wire:model.live="month" />
    </x-ui.field>
    <p class="text-text-subtle">{{ __('reports.period', ['period' => $period->label()]) }}</p>
</div>
<div wire:loading.delay wire:target="month"><x-ui.skeleton :lines="4" /></div>
