@php use App\Modules\Shared\Enums\Tone; @endphp
<div class="flex flex-col gap-lg">
    <div class="flex flex-col gap-md md:flex-row md:items-end md:justify-between">
        <x-ui.field :label="__('operations.departures.from')" for="from">
            <x-ui.input name="from" type="date" wire:model.live="from" />
        </x-ui.field>
        <x-ui.link-button variant="secondary" :href="route('operations.resources')">{{ __('operations.resources.title') }}</x-ui.link-button>
    </div>

    <div wire:loading.delay wire:target="from"><x-ui.skeleton :lines="5" /></div>
    <div wire:loading.remove wire:target="from">
        @if ($departures === [])
            <x-ui.empty-state :title="__('operations.departures.empty')" :description="__('operations.departures.empty_hint')" />
        @else
            <x-ui.table :caption="__('operations.departures.title')">
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-md py-sm">{{ __('operations.departures.departure') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('operations.departures.occupancy') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('operations.departures.guide') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('operations.departures.vehicle') }}</th>
                    </tr>
                </x-slot:head>
                @foreach ($departures as $departure)
                    @php
                        $assignment = $assignments->get($departure->ulid);
                        $count = $passengers[$departure->ulid] ?? 0;
                    @endphp
                    <tr wire:key="departure-{{ $departure->ulid }}">
                        <th scope="row" class="px-md py-sm text-left">
                            <a href="{{ route('operations.departures.show', $departure->ulid) }}" wire:navigate class="font-medium text-brand underline">{{ $departure->productName }}</a>
                            <p class="text-caption text-text-subtle">{{ $departure->startsAtLocal()->locale(app()->getLocale())->isoFormat('ddd ll, LT') }} · {{ $departure->destinationCity }}</p>
                        </th>
                        <td class="px-md py-sm">{{ __('operations.departures.occupancy_value', ['passengers' => $count, 'capacity' => $departure->capacity]) }}</td>
                        <td class="px-md py-sm">
                            @if ($assignment?->guide)
                                {{ $assignment->guide->name }}
                            @elseif ($count > 0)
                                <x-ui.badge :tone="Tone::Warning">{{ __('operations.departures.missing_guide') }}</x-ui.badge>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-md py-sm">
                            @if ($assignment?->vehicle)
                                {{ $assignment->vehicle->plate }}
                            @elseif ($count > 0)
                                <x-ui.badge :tone="Tone::Warning">{{ __('operations.departures.missing_vehicle') }}</x-ui.badge>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
        @endif
    </div>
</div>
