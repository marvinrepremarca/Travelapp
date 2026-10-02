@php
    use App\Modules\Shared\Enums\Tone;
    use Brick\Money\Money;
@endphp
<div class="flex max-w-page flex-col gap-lg">
    <x-ui.card :title="$accepted->title">
        <div class="flex flex-col gap-md">
            <p class="text-text-subtle">{{ __('bookings.from_quote', ['number' => $accepted->quoteNumber, 'version' => $accepted->version]) }}</p>
            <p>{{ __('bookings.convert.intro') }}</p>
            <ul class="flex flex-col divide-y divide-border">
                @foreach ($accepted->items as $index => $line)
                    <li class="flex justify-between gap-sm py-sm" wire:key="line-{{ $index }}">
                        <span>{{ $line['description'] }} <span class="text-caption text-text-subtle">· {{ $line['service_date'] }}</span></span>
                        <span class="font-medium">{{ $presenter->format(Money::ofMinor($line['sale_amount_minor'], $accepted->saleCurrency)) }}</span>
                    </li>
                @endforeach
            </ul>
            <p class="text-right font-medium">{{ __('bookings.total') }}: {{ $presenter->format($total) }}</p>
            @error('quote')<x-ui.alert :tone="Tone::Danger">{{ $message }}</x-ui.alert>@enderror
            <div class="flex justify-end">
                @if ($existing)
                    <x-ui.link-button :href="route('bookings.show', $existing)">{{ __('bookings.convert.view_booking') }} {{ $existing->number }}</x-ui.link-button>
                @else
                    <x-ui.button wire:click="convert" wire:loading.attr="disabled">{{ __('bookings.convert.action') }}</x-ui.button>
                @endif
            </div>
        </div>
    </x-ui.card>
</div>
