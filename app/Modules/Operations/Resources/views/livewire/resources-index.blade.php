@php use App\Modules\Shared\Enums\Tone; @endphp
<div class="flex flex-col gap-lg">
    @if (session('status'))<x-ui.alert :tone="Tone::Success" role="status">{{ session('status') }}</x-ui.alert>@endif

    <section class="flex flex-col gap-md" aria-labelledby="guides-heading">
        <div class="flex items-center justify-between gap-sm">
            <h2 id="guides-heading" class="text-heading-3 font-semibold">{{ __('operations.guides.title') }}</h2>
            <x-ui.link-button :href="route('operations.guides.create')">{{ __('operations.guides.create') }}</x-ui.link-button>
        </div>
        @if ($guides->isEmpty())
            <x-ui.empty-state :title="__('operations.guides.empty')" />
        @else
            <x-ui.table :caption="__('operations.guides.title')">
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-md py-sm">{{ __('operations.guides.fields.name') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('operations.guides.fields.phone') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('operations.guides.fields.languages') }}</th>
                        <th scope="col" class="px-md py-sm"><span class="sr-only">{{ __('shared.actions') }}</span></th>
                    </tr>
                </x-slot:head>
                @foreach ($guides as $guide)
                    <tr wire:key="guide-{{ $guide->ulid }}">
                        <th scope="row" class="px-md py-sm text-left font-medium">{{ $guide->name }} @unless ($guide->is_active)<x-ui.badge :tone="Tone::Neutral">{{ __('operations.inactive') }}</x-ui.badge>@endunless</th>
                        <td class="px-md py-sm">{{ $guide->phone }}</td>
                        <td class="px-md py-sm">{{ $guide->languages ?? '—' }}</td>
                        <td class="px-md py-sm"><a href="{{ route('operations.guides.edit', $guide) }}" wire:navigate class="text-brand underline">{{ __('shared.edit') }}<span class="sr-only"> {{ $guide->name }}</span></a></td>
                    </tr>
                @endforeach
            </x-ui.table>
        @endif
    </section>

    <section class="flex flex-col gap-md" aria-labelledby="vehicles-heading">
        <div class="flex items-center justify-between gap-sm">
            <h2 id="vehicles-heading" class="text-heading-3 font-semibold">{{ __('operations.vehicles.title') }}</h2>
            <x-ui.link-button :href="route('operations.vehicles.create')">{{ __('operations.vehicles.create') }}</x-ui.link-button>
        </div>
        @if ($vehicles->isEmpty())
            <x-ui.empty-state :title="__('operations.vehicles.empty')" />
        @else
            <x-ui.table :caption="__('operations.vehicles.title')">
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-md py-sm">{{ __('operations.vehicles.fields.plate') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('operations.vehicles.fields.description') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('operations.vehicles.fields.capacity') }}</th>
                        <th scope="col" class="px-md py-sm"><span class="sr-only">{{ __('shared.actions') }}</span></th>
                    </tr>
                </x-slot:head>
                @foreach ($vehicles as $vehicle)
                    <tr wire:key="vehicle-{{ $vehicle->ulid }}">
                        <th scope="row" class="px-md py-sm text-left font-medium">{{ $vehicle->plate }} @unless ($vehicle->is_active)<x-ui.badge :tone="Tone::Neutral">{{ __('operations.inactive') }}</x-ui.badge>@endunless</th>
                        <td class="px-md py-sm">{{ $vehicle->description }}</td>
                        <td class="px-md py-sm">{{ $vehicle->capacity }}</td>
                        <td class="px-md py-sm"><a href="{{ route('operations.vehicles.edit', $vehicle) }}" wire:navigate class="text-brand underline">{{ __('shared.edit') }}<span class="sr-only"> {{ $vehicle->plate }}</span></a></td>
                    </tr>
                @endforeach
            </x-ui.table>
        @endif
    </section>
</div>
