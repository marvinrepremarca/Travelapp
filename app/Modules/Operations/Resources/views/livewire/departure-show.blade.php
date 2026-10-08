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

    <div class="grid gap-lg lg:grid-cols-2">
        <x-ui.card :title="__('operations.incidents.title')">
            @error('incident')<x-ui.alert :tone="Tone::Danger" class="mb-md">{{ $message }}</x-ui.alert>@enderror
            @forelse ($incidents as $incident)
                <article wire:key="incident-{{ $incident->ulid }}" class="flex flex-col gap-xs border-b border-border py-sm">
                    <p class="flex flex-wrap items-center gap-sm">
                        <x-ui.badge :tone="$incident->severity->tone()">{{ $incident->severity->label() }}</x-ui.badge>
                        <span class="text-caption text-text-subtle">{{ $incident->reported_at->timezone($timezone)->locale(app()->getLocale())->isoFormat('lll') }}</span>
                        @if ($incident->isOpen())
                            <x-ui.badge :tone="Tone::Warning">{{ __('operations.incidents.open') }}</x-ui.badge>
                        @else
                            <x-ui.badge :tone="Tone::Success">{{ __('operations.incidents.closed') }}</x-ui.badge>
                        @endif
                    </p>
                    <p>{{ $incident->description }}</p>
                    @if ($incident->isOpen() && ! $closure)
                        <form wire:submit="resolveIncident('{{ $incident->ulid }}')" class="flex flex-col gap-xs sm:flex-row sm:items-end" novalidate>
                            <x-ui.field :label="__('operations.incidents.resolution')" for="resolutions.{{ $incident->ulid }}" class="grow">
                                <x-ui.input name="resolutions.{{ $incident->ulid }}" wire:model="resolutions.{{ $incident->ulid }}" />
                            </x-ui.field>
                            <x-ui.button type="submit" variant="secondary" wire:loading.attr="disabled" wire:target="resolveIncident">{{ __('operations.incidents.resolve') }}</x-ui.button>
                        </form>
                    @elseif (! $incident->isOpen())
                        <p class="text-caption text-text-subtle">{{ __('operations.incidents.resolution_value', ['resolution' => $incident->resolution]) }}</p>
                    @endif
                </article>
            @empty
                <p class="text-text-subtle">{{ __('operations.incidents.empty') }}</p>
            @endforelse

            @unless ($closure)
                <form wire:submit="reportIncident" class="mt-md flex flex-col gap-md" novalidate>
                    <x-ui.field :label="__('operations.incidents.severity')" for="severity">
                        <x-ui.select name="severity" wire:model="severity" :options="$severities" required />
                    </x-ui.field>
                    <x-ui.field :label="__('operations.incidents.description')" for="description">
                        <x-ui.input name="description" wire:model="description" required />
                    </x-ui.field>
                    <div><x-ui.button type="submit" wire:loading.attr="disabled" wire:target="reportIncident">{{ __('operations.incidents.report') }}</x-ui.button></div>
                </form>
            @endunless
        </x-ui.card>

        <x-ui.card :title="__('operations.closure.title')">
            @if ($closure)
                <x-ui.alert :tone="Tone::Success">{{ __('operations.closure.summary', [
                    'date' => $closure->closed_at->timezone($timezone)->locale(app()->getLocale())->isoFormat('lll'),
                    'attended' => $closure->attended,
                    'no_shows' => $closure->no_shows,
                ]) }}</x-ui.alert>
                @if ($closure->notes)<p class="mt-sm">{{ $closure->notes }}</p>@endif
            @else
                @error('closure')<x-ui.alert :tone="Tone::Danger" class="mb-md">{{ $message }}</x-ui.alert>@enderror
                <p class="mb-md text-caption text-text-subtle">{{ __('operations.closure.hint') }}</p>
                <form wire:submit="close" class="flex flex-col gap-md" novalidate>
                    <x-ui.field :label="__('operations.closure.no_shows')" for="noShows">
                        <x-ui.input name="noShows" type="number" min="0" wire:model="noShows" required />
                    </x-ui.field>
                    <x-ui.field :label="__('operations.closure.notes')" for="closingNotes">
                        <x-ui.input name="closingNotes" wire:model="closingNotes" />
                    </x-ui.field>
                    <div><x-ui.button type="submit" wire:loading.attr="disabled" wire:target="close">{{ __('operations.closure.close') }}</x-ui.button></div>
                </form>
            @endif
        </x-ui.card>
    </div>

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
                    <td class="px-md py-sm"><x-ui.capability-link route="bookings.show" :params="$passenger->bookingUlid" class="text-brand underline">{{ $passenger->bookingNumber }}</x-ui.capability-link></td>
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
