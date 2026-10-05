@php
    use App\Modules\Finance\Enums\PayableStatus;
    use App\Modules\Shared\Enums\Tone;
    $format = fn ($date) => $date->locale(app()->getLocale())->isoFormat('ll');
@endphp
<div class="flex flex-col gap-lg">
    @if (session('status'))<x-ui.alert :tone="Tone::Success" role="status">{{ session('status') }}</x-ui.alert>@endif

    <x-ui.card :title="__('finance.payables.by_supplier')">
        @if ($openTotals === [])
            <p class="text-text-subtle">{{ __('finance.payables.nothing_open') }}</p>
        @else
            <ul class="flex flex-col divide-y divide-border">
                @foreach ($openTotals as $row)
                    <li class="flex flex-wrap justify-between gap-sm py-sm" wire:key="total-{{ $row['supplier_id'] }}-{{ $row['total']->getCurrency()->getCurrencyCode() }}">
                        <button type="button" class="text-left font-medium text-brand underline" wire:click="$set('supplier', '{{ $row['supplier_id'] }}')">{{ $suppliers[$row['supplier_id']] ?? '—' }}</button>
                        <span>{{ trans_choice('finance.payables.items', $row['items'], ['count' => $row['items']]) }} · {{ $presenter->format($row['total']) }}
                            <span @class(['text-caption', 'text-danger' => $row['next_due']->lessThan($today), 'text-text-subtle' => ! $row['next_due']->lessThan($today)])>· {{ __('finance.payables.next_due', ['date' => $format($row['next_due'])]) }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.card>

    <div class="flex flex-col gap-md md:flex-row md:items-end">
        <x-ui.field :label="__('finance.payables.supplier')" for="supplier">
            <x-ui.select name="supplier" wire:model.live="supplier" :options="$suppliers" :placeholder="__('finance.payables.all_suppliers')" />
        </x-ui.field>
        <x-ui.field :label="__('finance.payables.status')" for="status">
            <x-ui.select name="status" wire:model.live="status" :placeholder="__('finance.payables.all_statuses')"
                :options="collect($statuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all()" />
        </x-ui.field>
        <label class="flex items-center gap-sm text-body">
            <input type="checkbox" wire:model.live="onlyOverdue" class="rounded-control border-border">
            {{ __('finance.payables.only_overdue') }}
        </label>
    </div>

    <div wire:loading.delay wire:target="supplier,status,onlyOverdue,gotoPage,nextPage,previousPage"><x-ui.skeleton :lines="4" /></div>

    <form wire:submit="settle" wire:loading.remove wire:target="supplier,status,onlyOverdue,gotoPage,nextPage,previousPage" class="flex flex-col gap-md" novalidate>
        @if ($payables->isEmpty())
            <x-ui.empty-state :title="__('finance.payables.empty')" />
        @else
            <x-ui.table :caption="__('finance.payables.title')">
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-md py-sm"><span class="sr-only">{{ __('finance.payables.selection') }}</span></th>
                        <th scope="col" class="px-md py-sm">{{ __('finance.payables.supplier') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('finance.payables.service') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('finance.payables.due') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('finance.payables.amount') }}</th>
                    </tr>
                </x-slot:head>
                @foreach ($payables as $payable)
                    <tr wire:key="payable-{{ $payable->ulid }}">
                        <td class="px-md py-sm">
                            @if ($payable->status === PayableStatus::Open)
                                <input type="checkbox" value="{{ $payable->ulid }}" wire:model="selected" aria-label="{{ __('finance.payables.select', ['service' => $payable->description]) }}">
                            @endif
                        </td>
                        <td class="px-md py-sm">{{ $payable->supplier->trade_name }}</td>
                        <td class="px-md py-sm">
                            {{ $payable->description }}
                            <p class="text-caption text-text-subtle">{{ $payable->booking_number }} · {{ $format($payable->service_date) }}</p>
                        </td>
                        <td class="px-md py-sm">
                            <span @class(['text-danger font-medium' => $payable->isOverdue($today)])>{{ $format($payable->due_date) }}</span>
                            <p><x-ui.badge :tone="$payable->status->tone()">{{ $payable->status->label() }}</x-ui.badge></p>
                            @if ($payable->payment_reference)<p class="text-caption text-text-subtle">{{ __('payments.list.reference', ['reference' => $payable->payment_reference]) }}</p>@endif
                        </td>
                        <td class="px-md py-sm font-medium">{{ $presenter->format($payable->amount()) }}</td>
                    </tr>
                @endforeach
            </x-ui.table>
            <div>{{ $payables->links() }}</div>

            @error('selected')<x-ui.alert :tone="Tone::Danger">{{ $message }}</x-ui.alert>@enderror
            <div class="flex flex-col gap-sm md:flex-row md:items-end">
                <x-ui.field :label="__('finance.payables.payment_reference')" for="paymentReference" :hint="__('finance.payables.settle_hint')">
                    <x-ui.input name="paymentReference" wire:model="paymentReference" hint />
                </x-ui.field>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="settle">{{ __('finance.payables.settle') }}</x-ui.button>
            </div>
        @endif
    </form>
</div>
