@php
    use App\Modules\Pricing\Livewire\PricingRulesManager;
    use App\Modules\Shared\Enums\Tone;
    use App\Modules\Shared\ValueObjects\Percentage;
    $tabs = [PricingRulesManager::TAB_MARKUPS, PricingRulesManager::TAB_FEES, PricingRulesManager::TAB_TAXES];
    $productOptions = collect($productTypes)->mapWithKeys(fn ($type) => [$type->value => $type->label()])->all();
    $channelOptions = collect($channels)->mapWithKeys(fn ($channel) => [$channel->value => $channel->label()])->all();
@endphp
<div class="flex flex-col gap-lg">
    <div role="tablist" aria-label="{{ __('pricing.rules.title') }}" class="flex flex-wrap gap-sm">
        @foreach ($tabs as $tabOption)
            <x-ui.button role="tab" :variant="$tab === $tabOption ? 'primary' : 'secondary'" aria-selected="{{ $tab === $tabOption ? 'true' : 'false' }}"
                wire:click="$set('tab', '{{ $tabOption }}')" wire:key="tab-{{ $tabOption }}">{{ __("pricing.rules.tabs.{$tabOption}") }}</x-ui.button>
        @endforeach
    </div>

    @if ($tab === PricingRulesManager::TAB_MARKUPS)
        <x-ui.card :title="__('pricing.rules.tabs.markups')">
            <p class="mb-md text-text-subtle">{{ __('pricing.rules.markup_help') }}</p>
            <ul class="mb-lg flex flex-col divide-y divide-border">
                @forelse ($markups as $rule)
                    <li class="flex flex-col gap-xs py-sm md:flex-row md:items-center md:justify-between" wire:key="markup-{{ $rule->ulid }}">
                        <div>
                            <p class="font-medium">{{ $rule->name }} <x-ui.badge :tone="$rule->is_active ? Tone::Success : Tone::Neutral">{{ $rule->is_active ? __('shared.active') : __('shared.inactive') }}</x-ui.badge></p>
                            <p class="text-caption text-text-subtle">
                                {{ $rule->kind->label() }}:
                                @if ($rule->rate_basis_points !== null) {{ __('suppliers.percent', ['value' => Percentage::fromBasisPoints($rule->rate_basis_points)->toPercentString()]) }} @else {{ $presenter->format($rule->amount()) }} @endif
                                · {{ $rule->product_type?->label() ?? __('pricing.any') }} · {{ $rule->sales_channel?->label() ?? __('pricing.any') }}
                                @if ($rule->destination_country) · {{ $rule->destination_country }} @endif
                                · {{ $rule->valid_from->toDateString() }} – {{ $rule->valid_until?->toDateString() ?? __('suppliers.open_ended') }}
                            </p>
                        </div>
                        <x-ui.button variant="ghost" wire:click="toggle('{{ PricingRulesManager::TAB_MARKUPS }}', '{{ $rule->ulid }}')" wire:loading.attr="disabled">
                            {{ $rule->is_active ? __('suppliers.deactivate') : __('suppliers.activate') }}<span class="sr-only"> {{ $rule->name }}</span>
                        </x-ui.button>
                    </li>
                @empty
                    <li><x-ui.empty-state :title="__('pricing.rules.empty')" /></li>
                @endforelse
            </ul>
            <form wire:submit="addMarkup" class="grid gap-md md:grid-cols-3" novalidate>
                <x-ui.field :label="__('pricing.rules.fields.markup.name')" for="markup.name"><x-ui.input name="markup.name" wire:model="markup.name" /></x-ui.field>
                <x-ui.field :label="__('pricing.rules.fields.markup.kind')" for="markup.kind">
                    <x-ui.select name="markup.kind" wire:model.live="markup.kind" :options="collect($kinds)->mapWithKeys(fn ($kind) => [$kind->value => $kind->label()])->all()" />
                </x-ui.field>
                <x-ui.field :label="__('pricing.rules.fields.markup.value')" for="markup.value" :hint="__('pricing.rules.value_hint')"><x-ui.input name="markup.value" inputmode="decimal" wire:model="markup.value" hint /></x-ui.field>
                <x-ui.field :label="__('pricing.rules.fields.markup.currency')" for="markup.currency"><x-ui.input name="markup.currency" wire:model="markup.currency" maxlength="3" /></x-ui.field>
                <x-ui.field :label="__('pricing.rules.fields.markup.product_type')" for="markup.product_type"><x-ui.select name="markup.product_type" wire:model="markup.product_type" :options="$productOptions" :placeholder="__('pricing.any')" /></x-ui.field>
                <x-ui.field :label="__('pricing.rules.fields.markup.sales_channel')" for="markup.sales_channel"><x-ui.select name="markup.sales_channel" wire:model="markup.sales_channel" :options="$channelOptions" :placeholder="__('pricing.any')" /></x-ui.field>
                <x-ui.field :label="__('pricing.rules.fields.markup.supplier_id')" for="markup.supplier_id"><x-ui.select name="markup.supplier_id" wire:model="markup.supplier_id" :options="$suppliers" :placeholder="__('pricing.any')" /></x-ui.field>
                <x-ui.field :label="__('pricing.rules.fields.markup.destination_country')" for="markup.destination_country"><x-ui.input name="markup.destination_country" wire:model="markup.destination_country" maxlength="2" /></x-ui.field>
                <x-ui.field :label="__('pricing.rules.fields.markup.min_margin')" for="markup.min_margin"><x-ui.input name="markup.min_margin" inputmode="decimal" wire:model="markup.min_margin" /></x-ui.field>
                <x-ui.field :label="__('pricing.rules.fields.markup.priority')" for="markup.priority"><x-ui.input name="markup.priority" type="number" wire:model="markup.priority" /></x-ui.field>
                <x-ui.field :label="__('pricing.rules.fields.markup.valid_from')" for="markup.valid_from"><x-ui.input name="markup.valid_from" type="date" wire:model="markup.valid_from" /></x-ui.field>
                <x-ui.field :label="__('pricing.rules.fields.markup.valid_until')" for="markup.valid_until"><x-ui.input name="markup.valid_until" type="date" wire:model="markup.valid_until" /></x-ui.field>
                <div class="md:col-span-3"><x-ui.button type="submit" wire:loading.attr="disabled" wire:target="addMarkup">{{ __('pricing.rules.add') }}</x-ui.button></div>
            </form>
        </x-ui.card>
    @elseif ($tab === PricingRulesManager::TAB_FEES)
        <x-ui.card :title="__('pricing.rules.tabs.fees')">
            <ul class="mb-lg flex flex-col divide-y divide-border">
                @forelse ($fees as $rule)
                    <li class="flex items-center justify-between gap-sm py-sm" wire:key="fee-{{ $rule->ulid }}">
                        <div>
                            <p class="font-medium">{{ $rule->name }} <x-ui.badge :tone="$rule->is_active ? Tone::Success : Tone::Neutral">{{ $rule->is_active ? __('shared.active') : __('shared.inactive') }}</x-ui.badge></p>
                            <p class="text-caption text-text-subtle">{{ $presenter->format($rule->amount()) }} · {{ $rule->basis->label() }} · {{ $rule->product_type?->label() ?? __('pricing.any') }} · {{ $rule->sales_channel?->label() ?? __('pricing.any') }}</p>
                        </div>
                        <x-ui.button variant="ghost" wire:click="toggle('{{ PricingRulesManager::TAB_FEES }}', '{{ $rule->ulid }}')" wire:loading.attr="disabled">
                            {{ $rule->is_active ? __('suppliers.deactivate') : __('suppliers.activate') }}<span class="sr-only"> {{ $rule->name }}</span>
                        </x-ui.button>
                    </li>
                @empty
                    <li><x-ui.empty-state :title="__('pricing.rules.empty')" /></li>
                @endforelse
            </ul>
            <form wire:submit="addFee" class="grid gap-md md:grid-cols-3" novalidate>
                <x-ui.field :label="__('pricing.rules.fields.fee.name')" for="fee.name"><x-ui.input name="fee.name" wire:model="fee.name" /></x-ui.field>
                <x-ui.field :label="__('pricing.rules.fields.fee.basis')" for="fee.basis"><x-ui.select name="fee.basis" wire:model="fee.basis" :options="collect($bases)->mapWithKeys(fn ($basis) => [$basis->value => $basis->label()])->all()" /></x-ui.field>
                <x-ui.field :label="__('pricing.rules.fields.fee.amount')" for="fee.amount"><x-ui.input name="fee.amount" inputmode="decimal" wire:model="fee.amount" /></x-ui.field>
                <x-ui.field :label="__('pricing.rules.fields.fee.currency')" for="fee.currency"><x-ui.input name="fee.currency" wire:model="fee.currency" maxlength="3" /></x-ui.field>
                <x-ui.field :label="__('pricing.rules.fields.fee.product_type')" for="fee.product_type"><x-ui.select name="fee.product_type" wire:model="fee.product_type" :options="$productOptions" :placeholder="__('pricing.any')" /></x-ui.field>
                <x-ui.field :label="__('pricing.rules.fields.fee.sales_channel')" for="fee.sales_channel"><x-ui.select name="fee.sales_channel" wire:model="fee.sales_channel" :options="$channelOptions" :placeholder="__('pricing.any')" /></x-ui.field>
                <x-ui.field :label="__('pricing.rules.fields.fee.valid_from')" for="fee.valid_from"><x-ui.input name="fee.valid_from" type="date" wire:model="fee.valid_from" /></x-ui.field>
                <x-ui.field :label="__('pricing.rules.fields.fee.valid_until')" for="fee.valid_until"><x-ui.input name="fee.valid_until" type="date" wire:model="fee.valid_until" /></x-ui.field>
                <div class="md:col-span-3"><x-ui.button type="submit" wire:loading.attr="disabled" wire:target="addFee">{{ __('pricing.rules.add') }}</x-ui.button></div>
            </form>
        </x-ui.card>
    @else
        <x-ui.card :title="__('pricing.rules.tabs.taxes')">
            <p class="mb-md text-text-subtle">{{ __('pricing.rules.tax_help') }}</p>
            <ul class="mb-lg flex flex-col divide-y divide-border">
                @forelse ($taxes as $rule)
                    <li class="flex items-center justify-between gap-sm py-sm" wire:key="tax-{{ $rule->ulid }}">
                        <div>
                            <p class="font-medium">{{ $rule->name }} <x-ui.badge :tone="$rule->is_active ? Tone::Success : Tone::Neutral">{{ $rule->is_active ? __('shared.active') : __('shared.inactive') }}</x-ui.badge></p>
                            <p class="text-caption text-text-subtle">{{ __('suppliers.percent', ['value' => Percentage::fromBasisPoints($rule->rate_basis_points)->toPercentString()]) }} · {{ $rule->valid_from->toDateString() }} – {{ $rule->valid_until?->toDateString() ?? __('suppliers.open_ended') }}
                                @if ($rule->exempt_product_types) · {{ __('pricing.rules.exempt', ['types' => collect($rule->exempt_product_types)->map(fn ($type) => \App\Modules\Shared\Enums\ProductType::from($type)->label())->implode(', ')]) }} @endif</p>
                        </div>
                        <x-ui.button variant="ghost" wire:click="toggle('{{ PricingRulesManager::TAB_TAXES }}', '{{ $rule->ulid }}')" wire:loading.attr="disabled">
                            {{ $rule->is_active ? __('suppliers.deactivate') : __('suppliers.activate') }}<span class="sr-only"> {{ $rule->name }}</span>
                        </x-ui.button>
                    </li>
                @empty
                    <li><x-ui.empty-state :title="__('pricing.rules.empty')" /></li>
                @endforelse
            </ul>
            <form wire:submit="addTax" class="grid gap-md md:grid-cols-2" novalidate>
                <x-ui.field :label="__('pricing.rules.fields.tax.name')" for="tax.name"><x-ui.input name="tax.name" wire:model="tax.name" /></x-ui.field>
                <x-ui.field :label="__('pricing.rules.fields.tax.rate')" for="tax.rate"><x-ui.input name="tax.rate" inputmode="decimal" wire:model="tax.rate" /></x-ui.field>
                <x-ui.field :label="__('pricing.rules.fields.tax.valid_from')" for="tax.valid_from"><x-ui.input name="tax.valid_from" type="date" wire:model="tax.valid_from" /></x-ui.field>
                <x-ui.field :label="__('pricing.rules.fields.tax.valid_until')" for="tax.valid_until"><x-ui.input name="tax.valid_until" type="date" wire:model="tax.valid_until" /></x-ui.field>
                <fieldset class="md:col-span-2">
                    <legend class="mb-xs font-medium">{{ __('pricing.rules.fields.tax.exempt') }}</legend>
                    <div class="flex flex-wrap gap-md">
                        @foreach ($productTypes as $type)
                            <label class="flex items-center gap-xs" wire:key="exempt-{{ $type->value }}">
                                <input type="checkbox" value="{{ $type->value }}" wire:model="tax.exempt" class="rounded-control border-border"> {{ $type->label() }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <div class="md:col-span-2"><x-ui.button type="submit" wire:loading.attr="disabled" wire:target="addTax">{{ __('pricing.rules.add') }}</x-ui.button></div>
            </form>
        </x-ui.card>
    @endif
</div>
