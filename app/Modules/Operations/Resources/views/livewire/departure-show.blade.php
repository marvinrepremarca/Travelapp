@php use App\Modules\Shared\Enums\Tone; @endphp
<div class="flex flex-col gap-lg">
    @if (session('status'))<x-ui.alert :tone="Tone::Success" role="status">{{ session('status') }}</x-ui.alert>@endif

    <x-ui.card>
        <dl class="grid gap-md md:grid-cols-4">
            <div><dt class="text-caption text-text-subtle">{{ __('operations.departures.starts') }}</dt><dd class="font-medium">{{ $departure->startsAtLocal()->locale(app()->getLocale())->isoFormat('LLLL') }}</dd></div>
            <div><dt class="text-caption text-text-subtle">{{ __('operations.departures.ends') }}</dt><dd>{{ $departure->endsAtLocal()->locale(app()->getLocale())->isoFormat('LT') }} <span class="text-caption text-text-subtle">({{ $departure->timezone }})</span></dd></div>
            <div><dt class="text-caption text-text-subtle">{{ __('operations.departures.destination') }}</dt><dd>{{ $departure->destinationCity }}</dd></div>
            <div><dt class="text-caption text-text-subtle">{{ __('operations.departures.occupancy') }}</dt><dd>{{ __('operations.departures.occupancy_value', ['passengers' => count($passengers), 'capacity' => $departure->capacity]) }}</dd></div>
        </dl>
    </x-ui.card>

    <x-ui.card :title="__('operations.departures.assignment')">
        <form wire:submit="assign" class="grid gap-md md:grid-cols-3 md:items-end" novalidate>
            @error('assignment')<div class="md:col-span-3"><x-ui.alert :tone="Tone::Danger">{{ $message }}</x-ui.alert></div>@enderror
            <x-ui.field :label="__('operations.departures.guide')" for="guide">
                <x-ui.select name="guide" wire:model="guide" :options="$guides" :placeholder="__('operations.departures.no_guide')" />
            </x-ui.field>
            <x-ui.field :label="__('operations.departures.vehicle')" for="vehicle">
                <x-ui.select name="vehicle" wire:model="vehicle" :options="$vehicles" :placeholder="__('operations.departures.no_vehicle')" />
            </x-ui.field>
            <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="assign">{{ __('operations.departures.assign') }}</x-ui.button>
        </form>
    </x-ui.card>

    <div class="flex items-center justify-between gap-sm">
        <h2 class="text-heading-3 font-semibold">{{ __('operations.manifest.heading') }}</h2>
        <x-ui.link-button variant="secondary" :href="route('operations.manifest', $departure->ulid)">{{ __('operations.manifest.download') }}</x-ui.link-button>
    </div>
    @if ($passengers === [])
        <x-ui.empty-state :title="__('operations.manifest.empty')" />
    @else
        <x-ui.table :caption="__('operations.manifest.heading')">
            <x-slot:head>
                <tr>
                    <th scope="col" class="px-md py-sm">{{ __('operations.manifest.traveler') }}</th>
                    <th scope="col" class="px-md py-sm">{{ __('operations.manifest.booking') }}</th>
                    <th scope="col" class="px-md py-sm">{{ __('operations.manifest.age') }}</th>
                    <th scope="col" class="px-md py-sm">{{ __('operations.manifest.nationality') }}</th>
                    <th scope="col" class="px-md py-sm">{{ __('operations.manifest.document') }}</th>
                    <th scope="col" class="px-md py-sm">{{ __('operations.manifest.contact') }}</th>
                </tr>
            </x-slot:head>
            @foreach ($passengers as $index => $passenger)
                <tr wire:key="passenger-{{ $index }}">
                    <th scope="row" class="px-md py-sm text-left font-medium">{{ $passenger->travelerName }}</th>
                    <td class="px-md py-sm"><a href="{{ route('bookings.show', $passenger->bookingUlid) }}" wire:navigate class="text-brand underline">{{ $passenger->bookingNumber }}</a></td>
                    <td class="px-md py-sm">{{ $passenger->age }} · {{ $passenger->passengerType }}</td>
                    <td class="px-md py-sm">{{ $passenger->nationality }}</td>
                    <td class="px-md py-sm">{{ $passenger->maskedDocument ?: '—' }}</td>
                    <td class="px-md py-sm">{{ $passenger->contactPhone ?? '—' }}</td>
                </tr>
            @endforeach
        </x-ui.table>
    @endif

    <div><x-ui.link-button variant="secondary" :href="route('operations.departures')">{{ __('operations.departures.back') }}</x-ui.link-button></div>
</div>
