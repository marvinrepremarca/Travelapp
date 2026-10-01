<div class="grid gap-lg lg:grid-cols-3">
    <div class="lg:col-span-2">
        @if ($rates->isEmpty())
            <x-ui.empty-state :title="__('pricing.rates.empty_title')" :description="__('pricing.rates.empty_description')" />
        @else
            <x-ui.table :caption="__('pricing.rates.title')">
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-md py-sm">{{ __('pricing.rates.fields.valid_on') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('pricing.rates.pair') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('pricing.rates.fields.rate') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('pricing.rates.source') }}</th>
                    </tr>
                </x-slot:head>
                @foreach ($rates as $rate)
                    <tr wire:key="rate-{{ $rate->id }}">
                        <td class="px-md py-sm">{{ $rate->valid_on->locale(app()->getLocale())->isoFormat('ll') }}</td>
                        <td class="px-md py-sm">{{ $rate->base_currency }} → {{ $rate->quote_currency }}</td>
                        <td class="px-md py-sm font-mono">{{ $rate->rate }}</td>
                        <td class="px-md py-sm"><x-ui.badge>{{ $rate->source->label() }}</x-ui.badge></td>
                    </tr>
                @endforeach
            </x-ui.table>
            <div class="mt-md">{{ $rates->links() }}</div>
        @endif
    </div>

    @if ($canManage)
        <x-ui.card :title="__('pricing.rates.record_title')">
            <form wire:submit="record" class="flex flex-col gap-md" novalidate>
                <div class="grid grid-cols-2 gap-sm">
                    <x-ui.field :label="__('pricing.rates.fields.base')" for="base"><x-ui.input name="base" wire:model="base" maxlength="3" /></x-ui.field>
                    <x-ui.field :label="__('pricing.rates.fields.quote')" for="quote"><x-ui.input name="quote" wire:model="quote" maxlength="3" /></x-ui.field>
                </div>
                <x-ui.field :label="__('pricing.rates.fields.rate')" for="rate" :hint="__('pricing.rates.rate_hint')">
                    <x-ui.input name="rate" inputmode="decimal" wire:model="rate" hint />
                </x-ui.field>
                <x-ui.field :label="__('pricing.rates.fields.valid_on')" for="valid_on">
                    <x-ui.input name="valid_on" type="date" wire:model="valid_on" />
                </x-ui.field>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="record">{{ __('pricing.rates.record') }}</x-ui.button>
                <x-ui.button type="button" variant="secondary" wire:click="fetchOfficial" wire:loading.attr="disabled" wire:target="fetchOfficial">
                    <span wire:loading.remove wire:target="fetchOfficial">{{ __('pricing.rates.fetch_official') }}</span>
                    <span wire:loading wire:target="fetchOfficial">{{ __('shared.loading') }}</span>
                </x-ui.button>
            </form>
        </x-ui.card>
    @endif
</div>
