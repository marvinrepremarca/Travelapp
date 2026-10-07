@php
    use App\Modules\Shared\Enums\Tone;
@endphp
<div class="flex flex-col gap-lg">
    <a href="{{ route('portal.shop') }}" wire:navigate class="text-caption font-medium text-brand underline">{{ __('portal.shop.back') }}</a>
    <header class="flex flex-col gap-xs">
        <p class="text-caption text-text-subtle">{{ $product->type->label() }} · {{ $product->destinationCity }}</p>
        <h1 class="text-heading-1 font-semibold">{{ $product->name }}</h1>
        @if ($product->description)<p class="text-text-subtle">{{ $product->description }}</p>@endif
    </header>

    @if ($requested)
        <x-ui.alert :tone="Tone::Success" role="status">{{ __('portal.shop.requested') }}</x-ui.alert>
    @endif
    @error('request')<x-ui.alert :tone="Tone::Danger" role="alert">{{ $message }}</x-ui.alert>@enderror

    <x-ui.card :title="__('portal.shop.request_title')">
        <form wire:submit="request" class="grid gap-md md:grid-cols-2" novalidate>
            <x-ui.field :label="__('portal.shop.fields.departure')" for="departure">
                <x-ui.select name="departure" wire:model.live="departure" :options="collect($product->departures)->mapWithKeys(fn ($departure) => [$departure->ulid => __('portal.shop.departure_option', [
                    'date' => $departure->startsAtLocal()->translatedFormat(config('travel.communications.notice_datetime_format')),
                    'seats' => $departure->capacity - $departure->reservedSeats,
                ])])->all()" required />
            </x-ui.field>
            <x-ui.field :label="__('portal.shop.fields.seats')" for="seats">
                <x-ui.input name="seats" type="number" min="1" :max="config('travel.portal.shop_max_seats')" wire:model.live.debounce.300ms="seats" required />
            </x-ui.field>
            <x-ui.field :label="__('portal.shop.fields.contactName')" for="contactName">
                <x-ui.input name="contactName" wire:model="contactName" autocomplete="name" required />
            </x-ui.field>
            <x-ui.field :label="__('portal.shop.fields.email')" for="email">
                <x-ui.input name="email" type="email" wire:model="email" autocomplete="email" required />
            </x-ui.field>
            <x-ui.field :label="__('portal.shop.fields.phone')" for="phone">
                <x-ui.input name="phone" type="tel" wire:model="phone" autocomplete="tel" required />
            </x-ui.field>
            <div class="flex flex-col justify-end">
                <p class="text-caption text-text-subtle">{{ __('portal.shop.estimate', ['seats' => $seatsCount]) }}</p>
                <p class="text-heading-3 font-semibold">{{ $estimate ?? __('portal.shop.price_on_request') }}</p>
            </div>
            <div class="md:col-span-2">
                <label class="flex items-start gap-sm">
                    <input type="checkbox" id="acceptsDataProcessing" wire:model="acceptsDataProcessing" @error('acceptsDataProcessing') aria-invalid="true" aria-describedby="acceptsDataProcessing-error" @enderror>
                    <span>{{ __('portal.shop.consent', ['version' => config('travel.privacy.policy_version')]) }}</span>
                </label>
                @error('acceptsDataProcessing')<p id="acceptsDataProcessing-error" class="text-caption text-danger">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="request">{{ __('portal.shop.send') }}</x-ui.button>
                <p class="mt-xs text-caption text-text-subtle">{{ __('portal.shop.disclaimer') }}</p>
            </div>
        </form>
    </x-ui.card>
</div>
