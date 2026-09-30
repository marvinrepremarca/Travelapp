<div class="flex flex-col gap-lg">
    <x-ui.field :label="__('organization.holidays_screen.year')" for="year" class="max-w-form">
        <x-ui.select name="year" wire:model.live="year" :options="array_combine($years, $years)" />
    </x-ui.field>

    <div class="grid gap-lg md:grid-cols-2">
        <x-ui.card :title="__('organization.holidays_screen.calendar', ['year' => $year])">
            <div wire:loading.delay wire:target="year"><x-ui.skeleton :lines="6" /></div>
            <ul class="flex flex-col divide-y divide-border" wire:loading.remove wire:target="year">
                @foreach ($holidays as $holiday)
                    <li class="flex items-center justify-between gap-sm py-sm" wire:key="holiday-{{ $holiday->date->toDateString() }}">
                        <div>
                            <p class="font-medium">{{ $holiday->name }}</p>
                            <p class="text-caption text-text-subtle">{{ $holiday->date->locale(app()->getLocale())->isoFormat('dddd LL') }}</p>
                        </div>
                        <x-ui.badge :tone="$holiday->source->tone()">{{ $holiday->source->label() }}</x-ui.badge>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>

        <div class="flex flex-col gap-lg">
            <x-ui.card :title="__('organization.holidays_screen.add_title')">
                <form wire:submit="addAdjustment" class="flex flex-col gap-md" novalidate>
                    <x-ui.field :label="__('organization.holidays_screen.fields.type')" for="type">
                        <x-ui.select name="type" wire:model="type"
                            :options="collect($types)->mapWithKeys(fn ($type) => [$type->value => $type->label()])->all()" />
                    </x-ui.field>
                    <x-ui.field :label="__('organization.holidays_screen.fields.date')" for="date">
                        <x-ui.input name="date" type="date" wire:model="date" required />
                    </x-ui.field>
                    <x-ui.field :label="__('organization.holidays_screen.fields.name')" for="name" :hint="__('organization.holidays_screen.name_hint')">
                        <x-ui.input name="name" wire:model="name" required hint />
                    </x-ui.field>
                    <div class="flex justify-end">
                        <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="addAdjustment">{{ __('shared.save') }}</x-ui.button>
                    </div>
                </form>
            </x-ui.card>

            <x-ui.card :title="__('organization.holidays_screen.adjustments_title')">
                @if ($adjustments->isEmpty())
                    <x-ui.empty-state :title="__('organization.holidays_screen.no_adjustments')" />
                @else
                    <ul class="flex flex-col divide-y divide-border">
                        @foreach ($adjustments as $adjustment)
                            <li class="flex items-center justify-between gap-sm py-sm" wire:key="adjustment-{{ $adjustment->ulid }}">
                                <div>
                                    <p class="font-medium">{{ $adjustment->name }}</p>
                                    <p class="text-caption text-text-subtle">{{ $adjustment->date->locale(app()->getLocale())->isoFormat('LL') }} · {{ $adjustment->type->label() }}</p>
                                </div>
                                <x-ui.button variant="ghost" wire:click="removeAdjustment('{{ $adjustment->ulid }}')" wire:loading.attr="disabled"
                                    wire:confirm="{{ __('organization.holidays_screen.confirm_remove', ['name' => $adjustment->name]) }}">
                                    {{ __('shared.delete') }}<span class="sr-only"> {{ $adjustment->name }}</span>
                                </x-ui.button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>
        </div>
    </div>
</div>
