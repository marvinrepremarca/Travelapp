<div class="flex flex-col gap-lg">
    <div>
        <x-ui.link-button :href="route('bookings.create')">{{ __('bookings.direct.open') }}</x-ui.link-button>
    </div>

    <div class="flex flex-col gap-md md:flex-row md:items-end">
        <x-ui.field :label="__('shared.search')" for="search" :hint="__('bookings.search_hint')">
            <x-ui.input name="search" type="search" wire:model.live.debounce.400ms="search" hint />
        </x-ui.field>
        <x-ui.field :label="__('bookings.columns.status')" for="status">
            <x-ui.select name="status" wire:model.live="status" :placeholder="__('bookings.all_statuses')"
                :options="collect($statuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all()" />
        </x-ui.field>
    </div>

    <div wire:loading.delay wire:target="search,status,gotoPage,nextPage,previousPage"><x-ui.skeleton :lines="4" /></div>

    <div wire:loading.remove wire:target="search,status,gotoPage,nextPage,previousPage">
        @if ($bookings->isEmpty())
            <x-ui.empty-state :title="__('bookings.empty_title')" :description="__('bookings.empty_description')" />
        @else
            <x-ui.table :caption="__('bookings.title')">
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-md py-sm">{{ __('bookings.columns.number') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('bookings.columns.customer') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('bookings.columns.status') }}</th>
                    </tr>
                </x-slot:head>
                @foreach ($bookings as $booking)
                    <tr wire:key="booking-{{ $booking->ulid }}">
                        <td class="px-md py-sm">
                            <a href="{{ route('bookings.show', $booking) }}" wire:navigate class="font-medium text-brand underline">{{ $booking->number }}</a>
                            <p class="text-caption text-text-subtle">{{ $booking->title }}</p>
                        </td>
                        <td class="px-md py-sm">{{ $booking->customer->display_name }}</td>
                        <td class="px-md py-sm"><x-ui.badge :tone="$booking->status->tone()">{{ $booking->status->label() }}</x-ui.badge></td>
                    </tr>
                @endforeach
            </x-ui.table>
            <div class="mt-md">{{ $bookings->links() }}</div>
        @endif
    </div>
</div>
