@php
    use App\Modules\Invoicing\Livewire\InvoicesIndex;
    use App\Modules\Shared\Enums\Tone;
    $date = fn ($instant) => $instant->timezone(config('travel.agency.timezone'))->locale(app()->getLocale())->isoFormat('ll');
@endphp
<div class="flex flex-col gap-lg">
    @if (session('status'))<x-ui.alert :tone="Tone::Success" role="status">{{ session('status') }}</x-ui.alert>@endif
    @error('issue')<x-ui.alert :tone="Tone::Danger">{{ $message }}</x-ui.alert>@enderror

    <div class="flex flex-wrap gap-sm" role="tablist" aria-label="{{ __('invoicing.title') }}">
        @foreach ([InvoicesIndex::TAB_READY, InvoicesIndex::TAB_ISSUED] as $option)
            <x-ui.button type="button" role="tab" :variant="$tab === $option ? 'primary' : 'secondary'" :aria-selected="$tab === $option ? 'true' : 'false'" wire:click="$set('tab', '{{ $option }}')">
                {{ __('invoicing.tabs.' . $option) }}
            </x-ui.button>
        @endforeach
    </div>

    <div wire:loading.delay wire:target="tab,type,search,gotoPage,nextPage,previousPage"><x-ui.skeleton :lines="5" /></div>

    <div wire:loading.remove wire:target="tab,type,search,gotoPage,nextPage,previousPage" class="flex flex-col gap-md">
        @if ($tab === InvoicesIndex::TAB_READY)
            <p class="text-text-subtle">{{ __('invoicing.ready_hint') }}</p>
            @if ($ready === [])
                <x-ui.empty-state :title="__('invoicing.ready_empty')" :description="__('invoicing.ready_empty_hint')" />
            @else
                <x-ui.table :caption="__('invoicing.tabs.ready')">
                    <x-slot:head>
                        <tr>
                            <th scope="col" class="px-md py-sm">{{ __('invoicing.booking') }}</th>
                            <th scope="col" class="px-md py-sm">{{ __('invoicing.customer') }}</th>
                            <th scope="col" class="px-md py-sm">{{ __('invoicing.total') }}</th>
                            <th scope="col" class="px-md py-sm"><span class="sr-only">{{ __('shared.actions') }}</span></th>
                        </tr>
                    </x-slot:head>
                    @foreach ($ready as $candidate)
                        <tr wire:key="ready-{{ $candidate->ulid }}">
                            <th scope="row" class="px-md py-sm text-left">
                                <x-ui.capability-link route="bookings.show" :params="$candidate->ulid" class="font-medium text-brand underline">{{ $candidate->number }}</x-ui.capability-link>
                                <p class="text-caption text-text-subtle">{{ $candidate->title }}</p>
                            </th>
                            <td class="px-md py-sm">{{ $candidate->customerName }}</td>
                            <td class="px-md py-sm font-medium">{{ $presenter->format($candidate->total) }}</td>
                            <td class="px-md py-sm">
                                <x-ui.button type="button" wire:click="issue('{{ $candidate->ulid }}')" wire:confirm="{{ __('invoicing.confirm_issue', ['booking' => $candidate->number]) }}" wire:loading.attr="disabled" wire:target="issue('{{ $candidate->ulid }}')">
                                    {{ __('invoicing.issue') }}
                                </x-ui.button>
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>
            @endif
        @else
            <div class="flex flex-col gap-md md:flex-row md:items-end">
                <x-ui.field :label="__('shared.search')" for="search">
                    <x-ui.input name="search" type="search" wire:model.live.debounce.400ms="search" />
                </x-ui.field>
                <x-ui.field :label="__('invoicing.document_type')" for="type">
                    <x-ui.select name="type" wire:model.live="type" :placeholder="__('invoicing.all_types')" :options="collect($types)->mapWithKeys(fn ($option) => [$option->value => $option->label()])->all()" />
                </x-ui.field>
            </div>
            @if ($invoices === null || $invoices->isEmpty())
                <x-ui.empty-state :title="__('invoicing.issued_empty')" />
            @else
                <x-ui.table :caption="__('invoicing.tabs.issued')">
                    <x-slot:head>
                        <tr>
                            <th scope="col" class="px-md py-sm">{{ __('invoicing.number') }}</th>
                            <th scope="col" class="px-md py-sm">{{ __('invoicing.customer') }}</th>
                            <th scope="col" class="px-md py-sm">{{ __('invoicing.issued_at') }}</th>
                            <th scope="col" class="px-md py-sm">{{ __('invoicing.total') }}</th>
                            <th scope="col" class="px-md py-sm">{{ __('invoicing.e_invoice') }}</th>
                        </tr>
                    </x-slot:head>
                    @foreach ($invoices as $invoice)
                        <tr wire:key="invoice-{{ $invoice->ulid }}">
                            <th scope="row" class="px-md py-sm text-left">
                                <a href="{{ route('invoicing.show', $invoice) }}" wire:navigate class="font-medium text-brand underline">{{ $invoice->number }}</a>
                                <p class="text-caption text-text-subtle">{{ $invoice->type->label() }} · {{ $invoice->booking_number }}</p>
                            </th>
                            <td class="px-md py-sm">{{ $invoice->customer_name }}</td>
                            <td class="px-md py-sm">{{ $date($invoice->issued_at) }}</td>
                            <td class="px-md py-sm font-medium">{{ $presenter->format($invoice->total()) }}</td>
                            <td class="px-md py-sm"><x-ui.badge :tone="$invoice->e_invoice_status->tone()">{{ $invoice->e_invoice_status->label() }}</x-ui.badge></td>
                        </tr>
                    @endforeach
                </x-ui.table>
                <div>{{ $invoices->links() }}</div>
            @endif
        @endif
    </div>
</div>
