@php
    use App\Modules\Bookings\Enums\BookingItemStatus;
    $formatDate = fn ($date) => $date->locale(app()->getLocale())->isoFormat('ll');
@endphp
<div class="flex flex-col gap-lg">
    <x-ui.card>
        <div class="flex flex-col gap-sm">
            <div class="flex flex-wrap items-center gap-sm">
                <x-ui.badge :tone="$booking->status->tone()">{{ $booking->status->label() }}</x-ui.badge>
                <span class="text-caption text-text-subtle">{{ $booking->customer->display_name }} · {{ $booking->sale_currency }}</span>
            </div>
            @if ($booking->quote_number)
                <p class="text-text-subtle">{{ __('bookings.from_quote', ['number' => $booking->quote_number, 'version' => $booking->quote_version]) }}</p>
            @endif
            <p class="font-medium">{{ __('bookings.total') }}: {{ $presenter->format($booking->saleTotal()) }}</p>
            @if ($missingPassengers > 0)
                <p class="text-caption text-warning">{{ __('bookings.passengers.pending_count', ['count' => $missingPassengers]) }}</p>
            @endif
        </div>
    </x-ui.card>

    <x-ui.card :title="__('bookings.items.title')">
        <ul class="flex flex-col divide-y divide-border">
            @foreach ($booking->items as $line)
                <li class="flex flex-wrap items-start justify-between gap-sm py-sm" wire:key="item-{{ $line->ulid }}">
                    <div>
                        <p class="font-medium">{{ $line->description }}</p>
                        <p class="text-caption text-text-subtle">
                            {{ $line->product_type->label() }} · {{ __('bookings.items.service', ['date' => $formatDate($line->service_date), 'nights' => $line->nights, 'passengers' => count($line->passenger_ages)]) }}
                            @if ($line->isOwnProduct()) · {{ __('bookings.items.own_product') }} @endif
                        </p>
                        @if ($line->supplier_confirmation)
                            <p class="text-caption">{{ __('bookings.items.confirmation', ['code' => $line->supplier_confirmation]) }}</p>
                        @endif
                        @if ($line->seat_hold_ulid && $line->status === BookingItemStatus::Confirmed)
                            <p class="text-caption text-success">{{ __('bookings.items.departure_held') }}</p>
                        @endif
                        @if ($line->passengers->isEmpty())
                            @unless ($line->status->isClosed())
                                <p class="text-caption text-warning">{{ __('bookings.passengers.missing') }}</p>
                            @endunless
                        @else
                            <p class="text-caption">{{ $line->passengers->map(fn ($passenger) => $passenger->traveler->fullName() . ' (' . __('bookings.passengers.age', ['age' => $passenger->age_at_service, 'type' => $passenger->passenger_type->label()]) . ')')->implode(', ') }}</p>
                        @endif
                        @if ($line->status_note)
                            <p class="text-caption text-text-subtle">{{ $line->status_note }}</p>
                        @endif
                        @if ($canSeeMargin)
                            <p class="text-caption text-text-subtle">{{ __('bookings.margin') }} {{ $presenter->format($line->marginAmount()) }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-sm">
                        <x-ui.badge :tone="$line->status->tone()">{{ $line->status->label() }}</x-ui.badge>
                        <span class="font-medium">{{ $presenter->format($line->saleAmount()) }}</span>
                        @unless ($line->status->isClosed())
                            <x-ui.button variant="ghost" wire:click="editPassengers('{{ $line->ulid }}')">
                                {{ __('bookings.passengers.assign') }}<span class="sr-only"> {{ $line->description }}</span>
                            </x-ui.button>
                        @endunless
                        @if ($line->status->allowedTransitions() !== [])
                            <x-ui.button variant="ghost" wire:click="manage('{{ $line->ulid }}')">
                                {{ __('bookings.items.manage') }}<span class="sr-only"> {{ $line->description }}</span>
                            </x-ui.button>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </x-ui.card>

    @if ($passengerItem)
        <x-ui.card :title="__('bookings.passengers.title', ['service' => $passengerItem->description])">
            <form wire:submit="savePassengers" class="flex flex-col gap-md" novalidate>
                <p class="text-text-subtle">{{ __('bookings.passengers.help') }}</p>
                @if ($travelers->isEmpty())
                    <p class="text-caption text-danger">{{ __('bookings.passengers.none') }}</p>
                @else
                    <fieldset class="flex flex-col gap-sm">
                        <legend class="sr-only">{{ __('bookings.passengers.title', ['service' => $passengerItem->description]) }}</legend>
                        @foreach ($travelers as $traveler)
                            <label class="flex items-center gap-sm" wire:key="traveler-{{ $traveler->ulid }}">
                                <input type="checkbox" value="{{ $traveler->ulid }}" wire:model="selectedTravelers">
                                {{ $traveler->fullName() }}
                                <span class="text-caption text-text-subtle">{{ __('bookings.passengers.age', ['age' => $traveler->ageAt($passengerItem->service_date), 'type' => $traveler->passengerTypeAt($passengerItem->service_date)->label()]) }}</span>
                            </label>
                        @endforeach
                    </fieldset>
                @endif
                @error('selectedTravelers')<p class="text-caption text-danger" role="alert">{{ $message }}</p>@enderror
                <div class="flex gap-sm">
                    <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="savePassengers">{{ __('bookings.passengers.save') }}</x-ui.button>
                    <x-ui.button type="button" variant="ghost" wire:click="$set('passengerItemUlid', '')">{{ __('bookings.actions.cancel') }}</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    @endif

    @if ($managed)
        <x-ui.card :title="__('bookings.actions.title', ['service' => $managed->description])">
            <form wire:submit="apply" class="grid gap-md md:grid-cols-2 md:items-end" novalidate>
                <x-ui.field :label="__('bookings.action_fields.status')" for="action.status">
                    <x-ui.select name="action.status" wire:model.live="action.status" :placeholder="__('quotes.choose')"
                        :options="collect($managed->status->allowedTransitions())->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all()" />
                </x-ui.field>
                @if ($action['status'] === BookingItemStatus::Confirmed->value)
                    <x-ui.field :label="__('bookings.action_fields.confirmation')" for="action.confirmation">
                        <x-ui.input name="action.confirmation" wire:model="action.confirmation" />
                    </x-ui.field>
                    @if ($managed->isOwnProduct())
                        <x-ui.field :label="__('bookings.action_fields.departure')" for="action.departure">
                            @if ($departures === [])
                                <p class="text-caption text-danger">{{ __('bookings.actions.no_departures') }}</p>
                            @else
                                <x-ui.select name="action.departure" wire:model="action.departure" :placeholder="__('quotes.choose')"
                                    :options="collect($departures)->mapWithKeys(fn ($slot) => [$slot->ulid => $slot->isOpen ? __('bookings.actions.departure_option', ['time' => $slot->startsAt, 'available' => $slot->availableSeats]) : __('bookings.actions.departure_closed', ['time' => $slot->startsAt])])->all()" />
                            @endif
                        </x-ui.field>
                    @endif
                @endif
                <div class="md:col-span-2">
                    <x-ui.field :label="__('bookings.action_fields.note')" for="action.note" :hint="__('bookings.actions.note_hint')">
                        <x-ui.input name="action.note" wire:model="action.note" hint />
                    </x-ui.field>
                </div>
                <div class="flex gap-sm md:col-span-2">
                    <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="apply">{{ __('bookings.actions.apply') }}</x-ui.button>
                    <x-ui.button type="button" variant="ghost" wire:click="$set('itemUlid', '')">{{ __('bookings.actions.cancel') }}</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    @endif
</div>
