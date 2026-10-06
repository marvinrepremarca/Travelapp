@php
    $date = fn ($instant) => $instant->timezone($timezone)->locale(app()->getLocale())->isoFormat('lll');
@endphp
<div class="flex flex-col gap-lg">
    @include('reports::livewire.partials.month-filter')

    <div wire:loading.remove wire:target="month" class="flex flex-col gap-lg">
        <div class="grid gap-md md:grid-cols-4">
            <x-ui.stat :label="__('reports.kpi.my_sales')" :value="$presenter->format($sales->total->sale)" :href="route('bookings.index')" />
            @if ($canSeeMargins)
                <x-ui.stat :label="__('reports.kpi.margin')" :value="$presenter->format($sales->total->margin())" :hint="$sales->total->marginRate() !== null ? __('reports.rate', ['rate' => $sales->total->marginRate()]) : null" />
            @endif
            <x-ui.stat :label="__('reports.kpi.bookings')" :value="(string) $sales->total->bookings" :href="route('bookings.index')" />
            <x-ui.stat :label="__('reports.kpi.conversion')" :value="$funnel->conversion() !== null ? __('reports.rate', ['rate' => $funnel->conversion()]) : '—'"
                :hint="__('reports.kpi.conversion_hint', ['accepted' => $funnel->quotesAccepted, 'sent' => $funnel->quotesSent])" :href="route('quotes.index')" />
        </div>

        <x-ui.card :title="__('reports.advisor.daily')">
            <x-ui.bar-chart :items="$chart" :caption="__('reports.management.daily_caption', ['period' => $period->label()])" />
        </x-ui.card>

        <div class="grid gap-lg md:grid-cols-3">
            <x-ui.card :title="__('reports.advisor.expiring_quotes')">
                @forelse ($quotes as $quote)
                    <a wire:key="quote-{{ $quote->ulid }}" href="{{ route('quotes.show', $quote) }}" wire:navigate class="flex flex-col border-b border-border py-xs hover:bg-muted">
                        <span class="font-medium">{{ $quote->number }} · {{ $quote->customer->display_name }}</span>
                        <span class="text-caption text-danger">{{ __('reports.advisor.expires', ['date' => $date($quote->valid_until)]) }}</span>
                    </a>
                @empty
                    <p class="text-text-subtle">{{ __('reports.advisor.no_expiring') }}</p>
                @endforelse
            </x-ui.card>

            <x-ui.card :title="__('reports.advisor.open_leads')">
                @forelse ($leads as $lead)
                    <a wire:key="lead-{{ $lead->ulid }}" href="{{ route('crm.leads.show', $lead) }}" wire:navigate class="flex flex-col border-b border-border py-xs hover:bg-muted">
                        <span class="font-medium">{{ $lead->contact_name }}</span>
                        <span class="text-caption text-text-subtle">{{ $lead->destination ?? __('crm.leads.no_destination') }} · {{ $lead->status->label() }}</span>
                    </a>
                @empty
                    <p class="text-text-subtle">{{ __('reports.advisor.no_leads') }}</p>
                @endforelse
            </x-ui.card>

            <x-ui.card :title="__('reports.advisor.upcoming_trips')">
                @forelse ($trips as $trip)
                    <a wire:key="trip-{{ $trip->ulid }}" href="{{ route('bookings.show', $trip) }}" wire:navigate class="flex flex-col border-b border-border py-xs hover:bg-muted">
                        <span class="font-medium">{{ $trip->number }} · {{ $trip->customer->display_name }}</span>
                        <span class="text-caption text-text-subtle">{{ __('reports.advisor.starts', ['date' => \Carbon\CarbonImmutable::parse((string) $trip->getAttribute('next_service_date'))->locale(app()->getLocale())->isoFormat('ll')]) }}</span>
                    </a>
                @empty
                    <p class="text-text-subtle">{{ __('reports.advisor.no_trips') }}</p>
                @endforelse
            </x-ui.card>
        </div>
    </div>
</div>
