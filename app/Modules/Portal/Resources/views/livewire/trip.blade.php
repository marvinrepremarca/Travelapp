@php
    use App\Modules\Shared\Enums\Tone;
    $date = fn ($value) => $value->locale(app()->getLocale())->isoFormat('dddd, LL');
@endphp
<div class="flex flex-col gap-lg">
    <header class="flex flex-col gap-xs">
        <p class="text-caption text-text-subtle">{{ __('portal.greeting', ['name' => $trip->customerName]) }}</p>
        <h1 class="text-heading-1 font-semibold">{{ $trip->title }}</h1>
        <p class="text-text-subtle">{{ __('portal.booking', ['number' => $trip->number]) }} · <x-ui.badge :tone="$trip->status->tone()">{{ $trip->status->label() }}</x-ui.badge></p>
    </header>

    <x-ui.card :title="__('portal.statement.title')">
        <dl class="grid gap-md md:grid-cols-4">
            <div><dt class="text-caption text-text-subtle">{{ __('portal.statement.total') }}</dt><dd class="font-medium">{{ $presenter->format($statement->total) }}</dd></div>
            <div><dt class="text-caption text-text-subtle">{{ __('portal.statement.paid') }}</dt><dd class="font-medium">{{ $presenter->format($statement->paid) }}</dd></div>
            <div><dt class="text-caption text-text-subtle">{{ __('portal.statement.balance') }}</dt><dd @class(['text-heading-3 font-semibold', 'text-danger' => $statement->isOverdue])>{{ $presenter->format($statement->balance->isNegative() ? $statement->balance->multipliedBy(0) : $statement->balance) }}</dd></div>
            <div><dt class="text-caption text-text-subtle">{{ __('portal.statement.due') }}</dt><dd>{{ $statement->dueDate ? $date($statement->dueDate) : '—' }}</dd></div>
        </dl>
        @error('pay')<x-ui.alert :tone="Tone::Info" class="mt-md">{{ $message }}</x-ui.alert>@enderror
        @if ($statement->balance->isPositive())
            <div class="mt-md">
                <x-ui.button type="button" wire:click="pay" wire:loading.attr="disabled" wire:target="pay">{{ __('portal.statement.pay', ['amount' => $presenter->format($statement->collectable())]) }}</x-ui.button>
                @if ($statement->pending->isPositive())
                    <p class="mt-xs text-caption text-text-subtle">{{ __('portal.statement.pending', ['amount' => $presenter->format($statement->pending)]) }}</p>
                @endif
            </div>
        @else
            <p class="mt-md text-success">{{ __('portal.statement.paid_in_full') }}</p>
        @endif
    </x-ui.card>

    <x-ui.card :title="__('portal.itinerary.title')">
        <ol class="flex flex-col gap-md">
            @foreach ($trip->services as $service)
                <li wire:key="service-{{ $service->ulid }}" class="flex flex-col gap-xs border-b border-border pb-sm md:flex-row md:items-start md:justify-between">
                    <div>
                        <p class="text-caption font-medium text-text-subtle">{{ $date($service->serviceDate) }}@if ($service->nights > 0) · {{ trans_choice('portal.itinerary.nights', $service->nights, ['count' => $service->nights]) }}@endif</p>
                        <p class="font-medium">{{ $service->description }}</p>
                        <p class="text-caption text-text-subtle">{{ $service->productType->label() }} · {{ $service->status->label() }}@if ($service->confirmationCode) · {{ __('portal.itinerary.code', ['code' => $service->confirmationCode]) }}@endif</p>
                    </div>
                    @if (isset($voucherUrls[$service->ulid]))
                        <x-ui.link-button variant="secondary" :href="$voucherUrls[$service->ulid]">{{ __('portal.itinerary.voucher') }}</x-ui.link-button>
                    @endif
                </li>
            @endforeach
        </ol>
        <div class="mt-md"><x-ui.link-button :href="$itineraryUrl">{{ __('portal.itinerary.download') }}</x-ui.link-button></div>
    </x-ui.card>

    <p class="text-caption text-text-subtle">{{ __('portal.personal_link') }} <a href="{{ route('portal.access') }}" class="text-brand underline">{{ __('portal.request_new') }}</a></p>
</div>
