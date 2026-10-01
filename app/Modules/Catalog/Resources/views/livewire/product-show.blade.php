@php
    use App\Modules\Shared\Enums\Tone;
    use Brick\Money\Money;
@endphp
<div class="flex flex-col gap-lg">
    <x-ui.card>
        <div class="flex flex-col gap-md">
            <div class="flex flex-wrap items-center gap-sm">
                <x-ui.badge :tone="$product->is_active ? Tone::Success : Tone::Neutral">{{ $product->is_active ? __('catalog.active') : __('catalog.inactive') }}</x-ui.badge>
                <span class="text-caption text-text-subtle">{{ $product->code }} · {{ $product->product_type->label() }}</span>
            </div>
            <dl class="grid gap-md md:grid-cols-3">
                <div><dt class="text-caption text-text-subtle">{{ __('catalog.fields.destination_city') }}</dt>
                    <dd>{{ __('catalog.destination', ['city' => $product->destination_city, 'country' => $product->destination_country]) }}</dd></div>
                <div><dt class="text-caption text-text-subtle">{{ __('catalog.fields.timezone') }}</dt><dd>{{ $product->timezone }}</dd></div>
                <div><dt class="text-caption text-text-subtle">{{ __('catalog.fields.duration_minutes') }}</dt>
                    <dd>{{ $product->duration_minutes ? __('catalog.duration', ['minutes' => $product->duration_minutes]) : '—' }}</dd></div>
            </dl>
            @if ($product->description)
                <p class="text-body">{{ $product->description }}</p>
            @endif
            @if ($canManage)
                <div class="flex justify-end gap-sm">
                    <x-ui.button variant="ghost" wire:click="toggleActive" wire:loading.attr="disabled">
                        {{ $product->is_active ? __('catalog.deactivate') : __('catalog.activate') }}
                    </x-ui.button>
                    <x-ui.link-button :href="route('catalog.edit', $product)" variant="secondary">{{ __('shared.edit') }}</x-ui.link-button>
                </div>
            @endif
        </div>
    </x-ui.card>

    <x-ui.card :title="__('catalog.seasons.title')">
        <p class="mb-md text-text-subtle">{{ __('catalog.seasons.help') }}</p>
        @if ($product->seasons->isEmpty())
            <x-ui.empty-state :title="__('catalog.seasons.empty')" />
        @else
            <x-ui.table :caption="__('catalog.seasons.title')">
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-md py-sm">{{ __('catalog.season_fields.name') }}</th>
                        @foreach ($passengerTypes as $type)
                            <th scope="col" class="px-md py-sm">{{ $type->label() }}</th>
                        @endforeach
                    </tr>
                </x-slot:head>
                @foreach ($product->seasons as $season)
                    <tr wire:key="season-{{ $season->ulid }}">
                        <td class="px-md py-sm">
                            <span class="font-medium">{{ $season->name }}</span>
                            <p class="text-caption text-text-subtle">{{ __('catalog.seasons.range', ['from' => $season->starts_on->toDateString(), 'to' => $season->ends_on->toDateString()]) }}</p>
                        </td>
                        @foreach ($passengerTypes as $type)
                            @php($rate = $season->rates->firstWhere('passenger_type', $type))
                            <td class="px-md py-sm">{{ $rate ? $presenter->format(Money::ofMinor($rate->net_amount_minor, $product->currency)) : __('catalog.seasons.no_rate') }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </x-ui.table>
        @endif

        @if ($canManage)
            <form wire:submit="addSeason" class="mt-md grid gap-md md:grid-cols-3 md:items-end" novalidate>
                <x-ui.field :label="__('catalog.season_fields.name')" for="season.name"><x-ui.input name="season.name" wire:model="season.name" required /></x-ui.field>
                <x-ui.field :label="__('catalog.season_fields.starts_on')" for="season.starts_on"><x-ui.input name="season.starts_on" type="date" wire:model="season.starts_on" required /></x-ui.field>
                <x-ui.field :label="__('catalog.season_fields.ends_on')" for="season.ends_on"><x-ui.input name="season.ends_on" type="date" wire:model="season.ends_on" required /></x-ui.field>
                @foreach ($passengerTypes as $type)
                    <x-ui.field :label="__('catalog.season_fields.' . $type->value) . ' (' . $product->currency . ')'" for="season.{{ $type->value }}">
                        <x-ui.input name="season.{{ $type->value }}" type="number" min="0" step="any" wire:model="season.{{ $type->value }}" />
                    </x-ui.field>
                @endforeach
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="addSeason">{{ __('catalog.seasons.add') }}</x-ui.button>
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
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="addDeparture">{{ __('catalog.departures.add') }}</x-ui.button>
            </form>
        @endif
    </x-ui.card>
</div>
