@php
    use App\Modules\Quotes\Enums\CustomerLinkState;
    use App\Modules\Shared\Enums\Tone;
@endphp
<div class="flex flex-col gap-lg">
    <header class="flex flex-col gap-xs">
        <h1 class="text-heading-1">{{ $quote->title }}</h1>
        <p class="text-text-subtle">{{ __('quotes.public.subtitle', ['number' => $quote->number, 'version' => $sentVersion->version]) }}</p>
        @if ($state === CustomerLinkState::Open)
            <p>{{ __('quotes.validity', ['date' => $sentVersion->valid_until->timezone($timezone)->locale(app()->getLocale())->isoFormat('lll')]) }}</p>
        @endif
    </header>

    @if ($state !== CustomerLinkState::Open)
        <x-ui.alert :tone="$state->tone()">{{ $state->message() }}</x-ui.alert>
    @endif

    @foreach ($options as $option)
        <x-ui.card :title="__('quotes.options.label', ['label' => $option['label']]) . ' · ' . $option['title']" wire:key="option-{{ $option['ulid'] }}">
            <div class="flex flex-col gap-md">
                @include('quotes::partials.itinerary', ['days' => $option['itinerary']])
                <p class="text-right text-heading-3 font-semibold">{{ __('quotes.options.total') }}: {{ $presenter->format($option['total']) }}</p>
            </div>
        </x-ui.card>
    @endforeach

    @if ($state === CustomerLinkState::Open)
        <x-ui.card :title="__('quotes.public.accept_title')">
            <form wire:submit="accept" class="flex flex-col gap-md" novalidate>
                <fieldset class="flex flex-col gap-sm">
                    <legend class="font-medium">{{ __('quotes.public.fields.optionUlid') }}</legend>
                    @foreach ($options as $option)
                        <label class="flex items-center gap-sm" wire:key="choice-{{ $option['ulid'] }}">
                            <input type="radio" name="optionUlid" value="{{ $option['ulid'] }}" wire:model="optionUlid">
                            {{ __('quotes.options.label', ['label' => $option['label']]) }} · {{ $option['title'] }} · {{ $presenter->format($option['total']) }}
                        </label>
                    @endforeach
                    @error('optionUlid')<p class="text-caption text-danger" role="alert">{{ $message }}</p>@enderror
                </fieldset>
                <x-ui.field :label="__('quotes.public.fields.acceptedBy')" for="acceptedBy">
                    <x-ui.input name="acceptedBy" wire:model="acceptedBy" autocomplete="name" required />
                </x-ui.field>
                <label class="flex items-start gap-sm">
                    <input type="checkbox" wire:model="termsAccepted" aria-describedby="terms-error">
                    <span>{{ __('quotes.public.terms') }}</span>
                </label>
                @error('termsAccepted')<p id="terms-error" class="text-caption text-danger" role="alert">{{ $message }}</p>@enderror
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="accept">{{ __('quotes.public.accept') }}</x-ui.button>
            </form>
        </x-ui.card>
    @endif
</div>
