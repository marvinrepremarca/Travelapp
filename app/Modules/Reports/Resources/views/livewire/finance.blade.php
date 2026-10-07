<div class="flex flex-col gap-lg">
    @include('reports::livewire.partials.month-filter')

    <div wire:loading.remove wire:target="month" class="flex flex-col gap-lg">
        <x-ui.card :title="__('reports.finance.receivables')">
            <div class="grid gap-md md:grid-cols-4">
                <x-ui.stat :label="__('reports.aging.overdue')" :value="$presenter->format($receivables->overdue)" :tone="$receivables->overdue->isPositive() ? 'danger' : null" />
                <x-ui.stat :label="__('reports.aging.due_soon', ['days' => config('travel.reports.due_soon_days')])" :value="$presenter->format($receivables->dueSoon)" />
                <x-ui.stat :label="__('reports.aging.later')" :value="$presenter->format($receivables->later)" />
                <x-ui.stat :label="__('reports.aging.total')" :value="$presenter->format($receivables->total())" :hint="trans_choice('reports.bookings_count', $receivables->count, ['count' => $receivables->count])" :href="route('bookings.index')" />
            </div>
            @if ($topReceivables !== [])
                <ul class="mt-md flex flex-col divide-y divide-border">
                    @foreach ($topReceivables as $item)
                        <li wire:key="receivable-{{ $item->bookingUlid }}" class="flex flex-wrap justify-between gap-sm py-xs">
                            <a href="{{ route('payments.booking', $item->bookingUlid) }}" wire:navigate class="font-medium text-brand underline">{{ $item->bookingNumber }} · {{ $item->customerName }}</a>
                            <span>{{ $presenter->format($item->balance) }}
                                @if ($item->dueDate)<span @class(['text-caption', 'text-danger' => $item->dueDate->isPast(), 'text-text-subtle' => ! $item->dueDate->isPast()])>· {{ __('reports.finance.due', ['date' => $item->dueDate->locale(app()->getLocale())->isoFormat('ll')]) }}</span>@endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
            <div class="mt-md"><x-ui.link-button variant="secondary" :href="route('reports.export', ['report' => 'receivables'])">{{ __('reports.export.receivables') }}</x-ui.link-button></div>
        </x-ui.card>

        <x-ui.card :title="__('reports.finance.payables')">
            <div class="grid gap-md md:grid-cols-4">
                <x-ui.stat :label="__('reports.aging.overdue')" :value="$presenter->format($payables->overdue)" :tone="$payables->overdue->isPositive() ? 'danger' : null" :href="route('finance.payables', ['onlyOverdue' => 1])" />
                <x-ui.stat :label="__('reports.aging.due_soon', ['days' => config('travel.reports.due_soon_days')])" :value="$presenter->format($payables->dueSoon)" :href="route('finance.payables')" />
                <x-ui.stat :label="__('reports.aging.later')" :value="$presenter->format($payables->later)" />
                <x-ui.stat :label="__('reports.aging.total')" :value="$presenter->format($payables->total())" :hint="trans_choice('reports.payables_count', $payables->count, ['count' => $payables->count])" :href="route('finance.payables')" />
            </div>
        </x-ui.card>

        <div class="grid gap-lg md:grid-cols-2">
            <x-ui.card :title="__('reports.pie.receivables')">
                <x-ui.pie-chart :items="$receivablesPie" :caption="__('reports.pie.receivables')" :empty="__('reports.pie.empty')" />
            </x-ui.card>
            <x-ui.card :title="__('reports.pie.payables')">
                <x-ui.pie-chart :items="$payablesPie" :caption="__('reports.pie.payables')" :empty="__('reports.pie.empty')" />
            </x-ui.card>
        </div>

        <div class="grid gap-lg md:grid-cols-2">
            <x-ui.card :title="__('reports.finance.cash')">
                @forelse ($cash as $index => $session)
                    <p wire:key="cash-{{ $index }}" class="flex justify-between gap-sm border-b border-border py-xs">
                        <span>{{ $session['branch'] }} <span class="text-caption text-text-subtle">· {{ __('reports.finance.opened', ['date' => $session['opened_at']->timezone($timezone)->locale(app()->getLocale())->isoFormat('lll')]) }}</span></span>
                        <span class="font-medium">{{ $presenter->format($session['expected']) }}</span>
                    </p>
                @empty
                    <p class="text-text-subtle">{{ __('reports.finance.no_cash') }}</p>
                @endforelse
                <a href="{{ route('finance.cash') }}" wire:navigate class="mt-sm inline-block text-caption font-medium text-brand underline">{{ __('reports.see_detail') }}</a>
            </x-ui.card>

            <x-ui.card :title="__('reports.finance.invoicing', ['period' => $period->label()])">
                @foreach ($types as $type)
                    <p wire:key="invoicing-{{ $type->value }}" class="flex justify-between gap-sm border-b border-border py-xs">
                        <span>{{ $type->label() }}</span><span class="font-medium">{{ $presenter->format($invoicing[$type->value]) }}</span>
                    </p>
                @endforeach
                <a href="{{ route('invoicing.index', ['tab' => 'issued']) }}" wire:navigate class="mt-sm inline-block text-caption font-medium text-brand underline">{{ __('reports.see_detail') }}</a>
            </x-ui.card>
        </div>
    </div>
</div>
