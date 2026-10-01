<form wire:submit="save" class="flex max-w-page flex-col gap-lg" novalidate>
    <x-ui.card :title="__('quotes.fields.customer_ulid')">
        <div class="flex flex-col gap-md">
            @if ($selected)
                <p class="font-medium">{{ __('quotes.customer_selected', ['name' => $selected->display_name]) }}</p>
            @endif
            <x-ui.field :label="__('quotes.customer_search')" for="customerSearch" :hint="__('quotes.customer_search_hint')">
                <x-ui.input name="customerSearch" type="search" wire:model.live.debounce.400ms="customerSearch" hint />
            </x-ui.field>
            @error('customer_ulid')<p class="text-caption text-danger" role="alert">{{ $message }}</p>@enderror
            @if ($customerSearch !== '')
                @if ($matches->isEmpty())
                    <p class="text-text-subtle">{{ __('quotes.no_matches') }}</p>
                @else
                    <ul class="flex flex-col divide-y divide-border">
                        @foreach ($matches as $match)
                            <li class="flex items-center justify-between py-sm" wire:key="match-{{ $match->ulid }}">
                                <span>{{ $match->display_name }}</span>
                                <x-ui.button variant="ghost" type="button" wire:click="chooseCustomer('{{ $match->ulid }}')">
                                    {{ __('quotes.choose') }}<span class="sr-only"> {{ $match->display_name }}</span>
                                </x-ui.button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            @endif
        </div>
    </x-ui.card>

    <x-ui.card>
        <div class="grid gap-md md:grid-cols-3">
            <div class="md:col-span-3">
                <x-ui.field :label="__('quotes.fields.title')" for="title">
                    <x-ui.input name="title" wire:model="title" required />
                </x-ui.field>
            </div>
            <x-ui.field :label="__('quotes.fields.sale_currency')" for="sale_currency">
                <x-ui.input name="sale_currency" wire:model="sale_currency" maxlength="3" required />
            </x-ui.field>
            <x-ui.field :label="__('quotes.fields.sales_channel')" for="sales_channel">
                <x-ui.select name="sales_channel" wire:model="sales_channel" required
                    :options="collect($channels)->mapWithKeys(fn ($channel) => [$channel->value => $channel->label()])->all()" />
            </x-ui.field>
        </div>
    </x-ui.card>

    <div class="flex gap-sm">
        <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">{{ __('quotes.create') }}</x-ui.button>
        <x-ui.link-button variant="secondary" :href="route('quotes.index')">{{ __('shared.cancel') }}</x-ui.link-button>
    </div>
</form>
