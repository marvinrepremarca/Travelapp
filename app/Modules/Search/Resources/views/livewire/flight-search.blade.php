<div class="flex flex-col gap-lg">
    <x-ui.card>
        <form wire:submit="search" class="grid gap-md md:grid-cols-3 md:items-end" novalidate>
            <x-ui.field :label="__('search.flights.fields.origin')" for="criteria.origin" :hint="__('search.flights.place_hint')">
                <x-ui.combobox name="criteria.origin" model="lookup.origin" field="origin" :options="$suggestions['origin']" hint required />
            </x-ui.field>
            <x-ui.field :label="__('search.flights.fields.destination')" for="criteria.destination">
                <x-ui.combobox name="criteria.destination" model="lookup.destination" field="destination" :options="$suggestions['destination']" required />
            </x-ui.field>
            <x-ui.field :label="__('search.flights.fields.cabin')" for="criteria.cabin">
                <x-ui.select name="criteria.cabin" wire:model="criteria.cabin" :options="collect($cabins)->mapWithKeys(fn ($cabin) => [$cabin->value => $cabin->label()])->all()" />
            </x-ui.field>
            <x-ui.field :label="__('search.flights.fields.departure_date')" for="criteria.departure_date">
                <x-ui.input name="criteria.departure_date" type="date" wire:model="criteria.departure_date" required />
            </x-ui.field>
            <x-ui.field :label="__('search.flights.fields.return_date')" for="criteria.return_date">
                <x-ui.input name="criteria.return_date" type="date" wire:model="criteria.return_date" />
            </x-ui.field>
            <x-ui.field :label="__('search.flights.fields.ages')" for="criteria.ages" :hint="__('search.ages_hint')">
                <x-ui.input name="criteria.ages" wire:model="criteria.ages" hint required />
            </x-ui.field>
            <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="search">{{ __('search.search') }}</x-ui.button>
        </form>
    </x-ui.card>

    <div wire:loading.delay wire:target="search" role="status"><x-ui.skeleton :lines="5" /><span class="sr-only">{{ __('search.searching') }}</span></div>

    @if ($result)
        <div wire:loading.remove wire:target="search" class="flex flex-col gap-md">
            @if (session('status'))<x-ui.alert :tone="\App\Modules\Shared\Enums\Tone::Success" role="status">{{ session('status') }}</x-ui.alert>@endif
            @if ($drafts === [])
                <p class="text-caption text-text-subtle">{{ __('search.no_drafts') }}</p>
            @else
                <x-ui.field :label="__('search.target_quote')" for="targetQuote">
                    <x-ui.select name="targetQuote" wire:model="targetQuote" :options="$drafts" :placeholder="__('quotes.choose')" />
                </x-ui.field>
            @endif
            @error('targetQuote')<x-ui.alert :tone="\App\Modules\Shared\Enums\Tone::Danger">{{ $message }}</x-ui.alert>@enderror
            @if ($result->unavailableProviders !== [])
                <x-ui.alert :tone="\App\Modules\Shared\Enums\Tone::Warning">{{ __('search.partial', ['providers' => implode(', ', $result->unavailableProviders)]) }}</x-ui.alert>
            @endif
            @if ($result->offers === [])
                <x-ui.empty-state :title="__('search.empty_title')" :description="__('search.empty_description')" />
            @else
                <ul class="flex flex-col gap-sm">
                    @foreach ($result->offers as $offer)
                        @php($sale = $prices[$offer->providerKey . $offer->offerId] ?? null)
                        <li wire:key="flight-{{ $offer->providerKey }}-{{ $offer->offerId }}">
                            <x-ui.card>
                                <div class="flex flex-wrap items-start justify-between gap-md">
                                    <div class="flex flex-col gap-xs">
                                        @foreach ([__('search.flights.outbound') => $offer->outbound, __('search.flights.inbound') => $offer->inbound] as $label => $segments)
                                            @if ($segments !== [])
                                                <p class="font-medium">{{ $label }}:
                                                    @foreach ($segments as $segment)
                                                        {{ $segment->origin }} {{ substr($segment->departsAtLocal, 11, 5) }} → {{ $segment->destination }} {{ substr($segment->arrivesAtLocal, 11, 5) }} · {{ $segment->carrierName }} {{ $segment->flightNumber }}
                                                    @endforeach
                                                </p>
                                            @endif
                                        @endforeach
                                        <p class="text-caption text-text-subtle">
                                            {{ trans_choice('search.flights.stops', $offer->stops(), ['count' => $offer->stops()]) }} ·
                                            {{ $offer->refundable ? __('search.refundable') : __('search.non_refundable') }} ·
                                            {{ __('search.provider', ['provider' => $offer->providerKey]) }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        @if ($sale)
                                            <p class="text-heading-3 font-semibold">{{ $presenter->format($sale) }}</p>
                                        @else
                                            <p class="text-caption text-warning">{{ __('search.no_rate') }}</p>
                                        @endif
                                        <p class="text-caption text-text-subtle">{{ __('search.net', ['amount' => $presenter->format($offer->totalNet)]) }}</p>
                                        @if ($drafts !== [])
                                            <x-ui.button variant="secondary" wire:click="addToQuote('{{ $offer->providerKey . $offer->offerId }}')" wire:loading.attr="disabled">{{ __('search.add_to_quote') }}</x-ui.button>
                                        @endif
                                    </div>
                                </div>
                            </x-ui.card>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif
</div>
