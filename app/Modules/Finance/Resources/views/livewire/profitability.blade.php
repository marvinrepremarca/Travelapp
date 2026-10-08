@php
    $columns = ['sale', 'cost', 'margin', 'commission', 'penalties', 'profit'];
    $values = fn ($figures) => [
        'sale' => $presenter->format($figures->sale),
        'cost' => $presenter->format($figures->cost),
        'margin' => $presenter->format($figures->margin()) . ($figures->marginRate() !== null ? ' (' . __('finance.profitability.rate', ['rate' => $figures->marginRate()]) . ')' : ''),
        'commission' => $presenter->format($figures->commission),
        'penalties' => $presenter->format($figures->penalties),
        'profit' => $presenter->format($figures->profit()),
    ];
@endphp
<div class="flex flex-col gap-lg">
    <p class="text-text-subtle">{{ __('finance.profitability.hint') }}</p>

    <div class="flex flex-col gap-md md:flex-row md:items-end">
        <x-ui.field :label="__('finance.profitability.month')" for="month">
            <x-ui.input name="month" type="month" wire:model.live="month" />
        </x-ui.field>
        @if ($canChooseBranch)
            <x-ui.field :label="__('finance.profitability.branch')" for="branch">
                <x-ui.select name="branch" wire:model.live="branch" :options="$branches" :placeholder="__('finance.profitability.all_branches')" />
            </x-ui.field>
        @endif
    </div>

    <div wire:loading.delay wire:target="month,branch,gotoPage,nextPage,previousPage"><x-ui.skeleton :lines="6" /></div>

    <div wire:loading.remove wire:target="month,branch,gotoPage,nextPage,previousPage" class="flex flex-col gap-lg">
        @if ($report->totals === [])
            <x-ui.empty-state :title="__('finance.profitability.empty', ['period' => $periodLabel])" />
        @else
            @foreach ([
                'totals' => [__('finance.profitability.totals', ['period' => $periodLabel]) => $report->totals],
                'owners' => collect($report->byOwner)->mapWithKeys(fn ($rows, $id) => [$owners[$id] ?? '—' => $rows])->all(),
                'branches' => collect($report->byBranch)->mapWithKeys(fn ($rows, $id) => [$branches[$id] ?? __('finance.profitability.no_branch') => $rows])->all(),
            ] as $group => $groupRows)
                <x-ui.table :caption="__('finance.profitability.by_' . $group)">
                    <x-slot:head>
                        <tr>
                            <th scope="col" class="px-md py-sm">{{ __('finance.profitability.group_' . $group) }}</th>
                            @foreach ($columns as $column)<th scope="col" class="px-md py-sm">{{ __('finance.profitability.' . $column) }}</th>@endforeach
                        </tr>
                    </x-slot:head>
                    @foreach ($groupRows as $name => $byCurrency)
                        @foreach ($byCurrency as $currency => $figures)
                            <tr wire:key="{{ $group }}-{{ $loop->parent->index }}-{{ $currency }}">
                                <th scope="row" class="px-md py-sm text-left font-medium">{{ $name }} <span class="text-caption text-text-subtle">{{ $currency }}</span></th>
                                @foreach ($values($figures) as $column => $value)
                                    <td @class(['px-md py-sm', 'font-semibold' => $column === 'profit', 'text-danger' => $column === 'profit' && $figures->profit()->isNegative()])>{{ $value }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                </x-ui.table>
            @endforeach

            <x-ui.table :caption="__('finance.profitability.by_bookings')">
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-md py-sm">{{ __('finance.profitability.group_bookings') }}</th>
                        @foreach ($columns as $column)<th scope="col" class="px-md py-sm">{{ __('finance.profitability.' . $column) }}</th>@endforeach
                    </tr>
                </x-slot:head>
                @foreach ($bookings as $booking)
                    <tr wire:key="booking-{{ $booking->ulid }}">
                        <th scope="row" class="px-md py-sm text-left">
                            <x-ui.capability-link route="bookings.show" :params="$booking->ulid" class="font-medium text-brand underline">{{ $booking->number }}</x-ui.capability-link>
                            <p class="text-caption text-text-subtle">{{ $booking->title }} · {{ $owners[$booking->ownerId] ?? '—' }} · {{ $booking->soldAt->timezone($timezone)->locale(app()->getLocale())->isoFormat('ll') }}</p>
                        </th>
                        @foreach ($values($booking->figures) as $column => $value)
                            <td @class(['px-md py-sm', 'font-semibold' => $column === 'profit', 'text-danger' => $column === 'profit' && $booking->figures->profit()->isNegative()])>{{ $value }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </x-ui.table>
            <div>{{ $bookings->links() }}</div>
        @endif
    </div>
</div>
