<div class="grid gap-lg lg:grid-cols-2">
    <x-ui.card :title="__('pricing.simulator.input')">
        <form wire:submit="calculate" class="grid gap-md md:grid-cols-2" novalidate>
            <x-ui.field :label="__('pricing.simulator.fields.net')" for="net" :hint="__('pricing.simulator.net_hint')">
                <x-ui.input name="net" inputmode="decimal" wire:model="net" hint required />
            </x-ui.field>
            <x-ui.field :label="__('pricing.simulator.fields.net_currency')" for="net_currency">
                <x-ui.input name="net_currency" wire:model="net_currency" maxlength="3" />
            </x-ui.field>
            <x-ui.field :label="__('pricing.simulator.fields.sale_currency')" for="sale_currency">
                <x-ui.input name="sale_currency" wire:model="sale_currency" maxlength="3" />
            </x-ui.field>
            <x-ui.field :label="__('pricing.simulator.fields.service_date')" for="service_date">
                <x-ui.input name="service_date" type="date" wire:model="service_date" />
            </x-ui.field>
            <x-ui.field :label="__('pricing.simulator.fields.product_type')" for="product_type">
                <x-ui.select name="product_type" wire:model="product_type"
                    :options="collect($productTypes)->mapWithKeys(fn ($type) => [$type->value => $type->label()])->all()" />
            </x-ui.field>
            <x-ui.field :label="__('pricing.simulator.fields.sales_channel')" for="sales_channel">
                <x-ui.select name="sales_channel" wire:model="sales_channel"
                    :options="collect($channels)->mapWithKeys(fn ($channel) => [$channel->value => $channel->label()])->all()" />
            </x-ui.field>
            <x-ui.field :label="__('pricing.simulator.fields.supplier_id')" for="supplier_id">
                <x-ui.select name="supplier_id" wire:model="supplier_id" :options="$suppliers" :placeholder="__('pricing.any')" />
            </x-ui.field>
            <x-ui.field :label="__('pricing.simulator.fields.destination_country')" for="destination_country">
                <x-ui.input name="destination_country" wire:model="destination_country" maxlength="2" />
            </x-ui.field>
            <x-ui.field :label="__('pricing.simulator.fields.passengers')" for="passengers">
                <x-ui.input name="passengers" type="number" min="1" wire:model="passengers" />
            </x-ui.field>
            <x-ui.field :label="__('pricing.simulator.fields.nights')" for="nights">
                <x-ui.input name="nights" type="number" min="0" wire:model="nights" />
            </x-ui.field>
            <div class="md:col-span-2">
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="calculate">{{ __('pricing.simulator.calculate') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card :title="__('pricing.simulator.result')">
        <div wire:loading.delay wire:target="calculate"><x-ui.skeleton :lines="5" /></div>
        <div wire:loading.remove wire:target="calculate">
            @if ($lines === [])
                <x-ui.empty-state :title="__('pricing.simulator.empty')" />
            @else
                <x-ui.table :caption="__('pricing.simulator.result')">
                    <x-slot:head>
                        <tr>
                            <th scope="col" class="px-md py-sm">{{ __('pricing.simulator.component') }}</th>
                            <th scope="col" class="px-md py-sm">{{ __('pricing.simulator.detail') }}</th>
                            <th scope="col" class="px-md py-sm text-right">{{ __('pricing.simulator.amount') }}</th>
                        </tr>
                    </x-slot:head>
                    @foreach ($lines as $index => $line)
                        <tr wire:key="line-{{ $index }}">
                            <td class="px-md py-sm">{{ $line['type'] }}</td>
                            <td class="px-md py-sm text-text-subtle">{{ $line['description'] }}</td>
                            <td class="px-md py-sm text-right font-mono">{{ $line['amount'] }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <th scope="row" colspan="2" class="px-md py-sm text-left">{{ __('pricing.simulator.total') }}</th>
                        <td class="px-md py-sm text-right font-mono font-semibold">{{ $total }}</td>
                    </tr>
                </x-ui.table>
                @if ($canSeeMargin)
                    <p class="mt-md">{{ __('pricing.simulator.margin', ['amount' => $margin]) }}</p>
                @endif
                @if ($rateInfo !== '')
                    <p class="mt-sm text-caption text-text-subtle">{{ $rateInfo }}</p>
                @endif
            @endif
        </div>
    </x-ui.card>
</div>
