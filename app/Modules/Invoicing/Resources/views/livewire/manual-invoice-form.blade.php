<form wire:submit="issue" class="flex flex-col gap-lg" novalidate>
    <p class="text-text-subtle">{{ __('invoicing.manual.intro') }}</p>

    <x-ui.card :title="__('invoicing.manual.fields.customerUlid')">
        <div class="flex flex-col gap-md">
            @if ($selected)
                <p class="font-medium">{{ __('invoicing.manual.customer_selected', ['name' => $selected->display_name]) }}</p>
            @endif
            <x-ui.field :label="__('invoicing.manual.customer_search')" for="customerSearch">
                <x-ui.input name="customerSearch" type="search" wire:model.live.debounce.400ms="customerSearch" />
            </x-ui.field>
            @error('customerUlid')<p class="text-caption text-danger" role="alert">{{ $message }}</p>@enderror
            @if ($customerSearch !== '')
                @if ($matches->isEmpty())
                    <p class="text-text-subtle">{{ __('invoicing.manual.no_matches') }}</p>
                @else
                    <ul class="flex flex-col divide-y divide-border">
                        @foreach ($matches as $match)
                            <li wire:key="customer-{{ $match->ulid }}">
                                <button type="button" class="w-full py-xs text-left text-brand underline" wire:click="chooseCustomer('{{ $match->ulid }}')">{{ $match->display_name }}</button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            @endif
        </div>
    </x-ui.card>

    <x-ui.card :title="__('invoicing.manual.lines')">
        <div class="flex flex-col gap-md">
            <p class="text-caption text-text-subtle">{{ __('invoicing.manual.lines_help') }}</p>
            @error('lines')<p class="text-caption text-danger" role="alert">{{ $message }}</p>@enderror
            @foreach ($lines as $index => $line)
                <fieldset class="grid gap-md border-b border-border pb-md md:grid-cols-5 md:items-end" wire:key="line-{{ $index }}">
                    <legend class="sr-only">{{ __('invoicing.manual.line', ['number' => $index + 1]) }}</legend>
                    <x-ui.field :label="__('invoicing.manual.fields.lines.*.description')" for="lines.{{ $index }}.description" class="md:col-span-2">
                        <x-ui.input name="lines.{{ $index }}.description" wire:model="lines.{{ $index }}.description" />
                    </x-ui.field>
                    <x-ui.field :label="__('invoicing.manual.fields.lines.*.kind')" for="lines.{{ $index }}.kind">
                        <x-ui.select name="lines.{{ $index }}.kind" wire:model="lines.{{ $index }}.kind"
                            :options="collect($kinds)->mapWithKeys(fn ($kind) => [$kind->value => $kind->label()])->all()" />
                    </x-ui.field>
                    <x-ui.field :label="__('invoicing.manual.fields.lines.*.product_type')" for="lines.{{ $index }}.product_type">
                        <x-ui.select name="lines.{{ $index }}.product_type" wire:model="lines.{{ $index }}.product_type" :placeholder="__('invoicing.manual.no_product_type')"
                            :options="collect($productTypes)->mapWithKeys(fn ($type) => [$type->value => $type->label()])->all()" />
                    </x-ui.field>
                    <x-ui.field :label="__('invoicing.manual.amount_in', ['currency' => $currency])" for="lines.{{ $index }}.amount">
                        <x-ui.input name="lines.{{ $index }}.amount" type="number" min="0" step="any" inputmode="decimal" wire:model="lines.{{ $index }}.amount" />
                    </x-ui.field>
                    @if (count($lines) > 1)
                        <div class="md:col-span-5">
                            <x-ui.button type="button" variant="secondary" wire:click="removeLine({{ $index }})">{{ __('invoicing.manual.remove_line') }}</x-ui.button>
                        </div>
                    @endif
                </fieldset>
            @endforeach
            <div>
                <x-ui.button type="button" variant="secondary" wire:click="addLine">{{ __('invoicing.manual.add_line') }}</x-ui.button>
            </div>
        </div>
    </x-ui.card>

    <div>
        <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="issue" wire:confirm="{{ __('invoicing.manual.confirm') }}">{{ __('invoicing.manual.submit') }}</x-ui.button>
    </div>
</form>
