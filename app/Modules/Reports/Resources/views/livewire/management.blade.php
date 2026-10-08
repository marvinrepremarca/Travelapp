@php
    $growth = $sales->total->growthAgainst($previous);
    $rate = fn ($figures) => $figures->marginRate() !== null ? __('reports.rate', ['rate' => $figures->marginRate()]) : '—';
@endphp
<div class="flex flex-col gap-lg">
    @include('reports::livewire.partials.month-filter')

    <div wire:loading.remove wire:target="month" class="flex flex-col gap-lg">
        <div class="grid gap-md md:grid-cols-4">
            <x-ui.stat :label="__('reports.kpi.sales')" :value="$presenter->format($sales->total->sale)"
                :hint="$growth === null ? __('reports.no_previous') : __('reports.vs_previous', ['growth' => $growth])"
                :tone="$growth === null ? null : (str_starts_with($growth, '-') ? 'danger' : 'success')"
                :href="\App\Modules\Shared\Capabilities\Capabilities::routeUrl('bookings.index')" />
            <x-ui.stat :label="__('reports.kpi.margin')" :value="$presenter->format($sales->total->margin())" :hint="$rate($sales->total)" :href="\App\Modules\Shared\Capabilities\Capabilities::routeUrl('finance.profitability', ['month' => $period->key()])" />
            <x-ui.stat :label="__('reports.kpi.bookings')" :value="(string) $sales->total->bookings"
                :hint="$sales->total->averageTicket() ? __('reports.kpi.ticket', ['amount' => $presenter->format($sales->total->averageTicket())]) : null"
                :href="\App\Modules\Shared\Capabilities\Capabilities::routeUrl('bookings.index')" />
            <x-ui.stat :label="__('reports.kpi.conversion')" :value="$funnel->conversion() !== null ? __('reports.rate', ['rate' => $funnel->conversion()]) : '—'"
                :hint="__('reports.kpi.conversion_hint', ['accepted' => $funnel->quotesAccepted, 'sent' => $funnel->quotesSent])" :href="\App\Modules\Shared\Capabilities\Capabilities::routeUrl('quotes.index')" />
        </div>

        <x-ui.card :title="__('reports.management.daily')">
            <x-ui.bar-chart :items="$chart" :caption="__('reports.management.daily_caption', ['period' => $period->label()])" />
        </x-ui.card>

        <div class="grid gap-lg md:grid-cols-2">
            <x-ui.card :title="__('reports.pie.by_product')">
                <x-ui.pie-chart :items="$productPie" :caption="__('reports.pie.by_product')" :empty="__('reports.pie.empty')" />
            </x-ui.card>
            <x-ui.card :title="__('reports.pie.by_branch')">
                <x-ui.pie-chart :items="$branchPie" :caption="__('reports.pie.by_branch')" :empty="__('reports.pie.empty')" />
            </x-ui.card>
        </div>

        <x-ui.card :title="__('reports.management.funnel')">
            <ol class="grid gap-md md:grid-cols-4">
                @foreach (['leads' => $funnel->leads, 'quotes_sent' => $funnel->quotesSent, 'quotes_accepted' => $funnel->quotesAccepted, 'bookings' => $funnel->bookings] as $stage => $count)
                    <li wire:key="funnel-{{ $stage }}" class="rounded-control bg-muted p-md">
                        <p class="text-caption text-text-subtle">{{ __('reports.funnel.' . $stage) }}</p>
                        <p class="text-heading-3 font-semibold">{{ $count }}</p>
                    </li>
                @endforeach
            </ol>
        </x-ui.card>

        <div class="grid gap-lg md:grid-cols-2">
            <x-ui.table :caption="__('reports.management.by_branch')">
                <x-slot:head>
                    <tr><th scope="col" class="px-md py-sm">{{ __('reports.branch') }}</th><th scope="col" class="px-md py-sm">{{ __('reports.kpi.sales') }}</th><th scope="col" class="px-md py-sm">{{ __('reports.kpi.margin') }}</th></tr>
                </x-slot:head>
                @forelse ($sales->byBranch as $branchId => $figures)
                    <tr wire:key="branch-{{ $branchId }}">
                        <th scope="row" class="px-md py-sm text-left font-medium">{{ $branches[$branchId] ?? __('reports.no_branch') }}</th>
                        <td class="px-md py-sm">{{ $presenter->format($figures->sale) }}</td>
                        <td class="px-md py-sm">{{ $presenter->format($figures->margin()) }} <span class="text-caption text-text-subtle">{{ $rate($figures) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-md py-sm text-text-subtle">{{ __('reports.empty') }}</td></tr>
                @endforelse
            </x-ui.table>

            <x-ui.table :caption="__('reports.management.top_owners')">
                <x-slot:head>
                    <tr><th scope="col" class="px-md py-sm">{{ __('reports.owner') }}</th><th scope="col" class="px-md py-sm">{{ __('reports.kpi.sales') }}</th><th scope="col" class="px-md py-sm">{{ __('reports.kpi.bookings') }}</th></tr>
                </x-slot:head>
                @forelse ($topOwners as $ownerId => $figures)
                    <tr wire:key="owner-{{ $ownerId }}">
                        <th scope="row" class="px-md py-sm text-left font-medium">{{ $owners[$ownerId] ?? '—' }}</th>
                        <td class="px-md py-sm">{{ $presenter->format($figures->sale) }}</td>
                        <td class="px-md py-sm">{{ $figures->bookings }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-md py-sm text-text-subtle">{{ __('reports.empty') }}</td></tr>
                @endforelse
            </x-ui.table>
        </div>

        <x-ui.table :caption="__('reports.management.by_product')">
            <x-slot:head>
                <tr><th scope="col" class="px-md py-sm">{{ __('reports.product') }}</th><th scope="col" class="px-md py-sm">{{ __('reports.kpi.sales') }}</th><th scope="col" class="px-md py-sm">{{ __('reports.kpi.margin') }}</th></tr>
            </x-slot:head>
            @forelse ($sales->byProduct as $product => $figures)
                <tr wire:key="product-{{ $product }}">
                    <th scope="row" class="px-md py-sm text-left font-medium">{{ $products[$product] ?? $product }}</th>
                    <td class="px-md py-sm">{{ $presenter->format($figures->sale) }}</td>
                    <td class="px-md py-sm">{{ $presenter->format($figures->margin()) }} <span class="text-caption text-text-subtle">{{ $rate($figures) }}</span></td>
                </tr>
            @empty
                <tr><td colspan="3" class="px-md py-sm text-text-subtle">{{ __('reports.empty') }}</td></tr>
            @endforelse
        </x-ui.table>

        <div class="flex flex-wrap gap-sm">
            <x-ui.link-button variant="secondary" :href="\App\Modules\Shared\Capabilities\Capabilities::routeUrl('reports.export', ['report' => 'sales_by_owner', 'month' => $period->key()])">{{ __('reports.export.sales_by_owner') }}</x-ui.link-button>
            <x-ui.link-button variant="secondary" :href="\App\Modules\Shared\Capabilities\Capabilities::routeUrl('reports.export', ['report' => 'sales_by_branch', 'month' => $period->key()])">{{ __('reports.export.sales_by_branch') }}</x-ui.link-button>
        </div>
        <p class="text-caption text-text-subtle">{{ __('reports.management.definition', ['currency' => $sales->currency]) }}</p>
    </div>
</div>
