@php
    use App\Modules\Shared\Enums\Tone;
    use Brick\Money\Money;
@endphp
<div class="flex flex-col gap-lg">
    @include('catalog::partials.product-header')

    <x-ui.card :title="__('catalog.seasons.title')">
        <p class="mb-md text-text-subtle">{{ __('catalog.seasons.help') }}</p>
        <label class="mb-md flex items-center gap-sm text-body"><input type="checkbox" wire:model.live="showPastSeasons" class="rounded-control border-border"> {{ __('catalog.seasons.show_past') }}</label>
        @if ($seasons->isEmpty())
            <x-ui.empty-state :title="__('catalog.seasons.empty')" />
        @else
            <x-ui.table :caption="__('catalog.seasons.title')">
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-md py-sm">{{ __('catalog.season_fields.name') }}</th>
                        @foreach ($passengerTypes as $type)
                            <th scope="col" class="px-md py-sm">{{ $type->label() }}</th>
                        @endforeach
                        @if ($canManage)<th scope="col" class="px-md py-sm"><span class="sr-only">{{ __('shared.actions') }}</span></th>@endif
                    </tr>
                </x-slot:head>
                @foreach ($seasons as $season)
                    <tr wire:key="season-{{ $season->ulid }}">
                        <td class="px-md py-sm">
                            <span class="font-medium">{{ $season->name }}</span>
                            <p class="text-caption text-text-subtle">{{ __('catalog.seasons.range', ['from' => $season->starts_on->toDateString(), 'to' => $season->ends_on->toDateString()]) }}</p>
                        </td>
                        @foreach ($passengerTypes as $type)
                            @php($rate = $season->rates->firstWhere('passenger_type', $type))
                            <td class="px-md py-sm">{{ $rate ? $presenter->format(Money::ofMinor($rate->net_amount_minor, $product->currency)) : __('catalog.seasons.no_rate') }}</td>
                        @endforeach
                        @if ($canManage)
                            <td class="px-md py-sm text-right">
                                <x-ui.button variant="ghost" wire:click="editSeason('{{ $season->ulid }}')" wire:loading.attr="disabled">{{ __('shared.edit') }}<span class="sr-only"> {{ $season->name }}</span></x-ui.button>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </x-ui.table>
            <div class="mt-md">{{ $seasons->links() }}</div>
        @endif

        @if ($canManage)
            @if ($editingSeason !== null)
                <h3 class="mt-md font-medium">{{ __('catalog.seasons.edit_title') }}</h3>
                <x-ui.alert>{{ __('catalog.seasons.edit_help') }}</x-ui.alert>
            @endif
            <form wire:submit="addSeason" class="mt-md grid gap-md md:grid-cols-3 md:items-end" novalidate>
                <x-ui.field :label="__('catalog.season_fields.name')" for="season.name"><x-ui.input name="season.name" wire:model="season.name" required /></x-ui.field>
                <x-ui.field :label="__('catalog.season_fields.starts_on')" for="season.starts_on"><x-ui.input name="season.starts_on" type="date" wire:model="season.starts_on" required /></x-ui.field>
                <x-ui.field :label="__('catalog.season_fields.ends_on')" for="season.ends_on"><x-ui.input name="season.ends_on" type="date" wire:model="season.ends_on" required /></x-ui.field>
                @foreach ($passengerTypes as $type)
                    <x-ui.field :label="__('catalog.season_fields.' . $type->value) . ' (' . $product->currency . ')'" for="season.{{ $type->value }}">
                        <x-ui.input name="season.{{ $type->value }}" type="number" min="0" step="any" wire:model="season.{{ $type->value }}" />
                    </x-ui.field>
                @endforeach
                <div class="flex flex-wrap gap-sm">
                    <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="addSeason">{{ $editingSeason === null ? __('catalog.seasons.add') : __('catalog.seasons.save') }}</x-ui.button>
                    @if ($editingSeason !== null)<x-ui.button type="button" variant="secondary" wire:click="cancelEdit">{{ __('shared.cancel') }}</x-ui.button>@endif
                </div>
            </form>
        @endif
    </x-ui.card>

    <x-ui.card :title="__('catalog.departures.title')">
        <p class="mb-md text-text-subtle">{{ __('catalog.departures.help') }}</p>
        @if ($departures->isEmpty())
            <x-ui.empty-state :title="__('catalog.departures.empty')" />
        @else
            <ul class="flex flex-col divide-y divide-border">
                @foreach ($departures as $departure)
                    <li class="flex flex-wrap items-center justify-between gap-sm py-sm" wire:key="departure-{{ $departure->ulid }}">
                        <div>
                            <p class="font-medium">{{ __('catalog.departures.when', ['date' => $departure->service_date->locale(app()->getLocale())->isoFormat('ll'), 'time' => substr($departure->starts_at, 0, 5)]) }}</p>
                            <p class="text-caption text-text-subtle">{{ __('catalog.departures.seats', ['available' => $departure->availableSeats(), 'capacity' => $departure->capacity]) }}</p>
                        </div>
                        <div class="flex items-center gap-sm">
                            <x-ui.badge :tone="$departure->status->tone()">{{ $departure->status->label() }}</x-ui.badge>
                            @if ($departure->availableSeats() === 0)
                                <x-ui.badge :tone="Tone::Danger">{{ __('catalog.departures.sold_out') }}</x-ui.badge>
                            @endif
                            @if ($canManage)
                                <x-ui.button variant="ghost" wire:click="editDeparture('{{ $departure->ulid }}')" wire:loading.attr="disabled">{{ __('shared.edit') }}</x-ui.button>
                                <x-ui.button variant="ghost" wire:click="toggleDeparture('{{ $departure->ulid }}')" wire:loading.attr="disabled">
                                    {{ $departure->status === \App\Modules\Catalog\Enums\DepartureStatus::Open ? __('catalog.departures.close') : __('catalog.departures.reopen') }}
                                </x-ui.button>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="mt-md">{{ $departures->links() }}</div>
        @endif

        @if ($canManage)
            <form wire:submit="addDeparture" class="mt-md grid gap-md md:grid-cols-4 md:items-end" novalidate>
                <x-ui.field :label="__('catalog.departure_fields.service_date')" for="departure.service_date"><x-ui.input name="departure.service_date" type="date" wire:model="departure.service_date" required /></x-ui.field>
                <x-ui.field :label="__('catalog.departure_fields.starts_at')" for="departure.starts_at"><x-ui.input name="departure.starts_at" type="time" wire:model="departure.starts_at" required /></x-ui.field>
                <x-ui.field :label="__('catalog.departure_fields.capacity')" for="departure.capacity"><x-ui.input name="departure.capacity" type="number" min="1" wire:model="departure.capacity" required /></x-ui.field>
                <div class="flex flex-wrap gap-sm">
                    <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="addDeparture">{{ $editingDeparture === null ? __('catalog.departures.add') : __('catalog.departures.save') }}</x-ui.button>
                    @if ($editingDeparture !== null)<x-ui.button type="button" variant="secondary" wire:click="cancelEdit">{{ __('shared.cancel') }}</x-ui.button>@endif
                </div>
            </form>
        @endif
    </x-ui.card>
</div>
