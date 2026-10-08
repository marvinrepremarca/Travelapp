@php
    use App\Modules\Invoicing\Enums\InvoiceLineKind;
    use App\Modules\Shared\Enums\Tone;
@endphp
<div class="flex flex-col gap-lg">
    @if (session('status'))<x-ui.alert :tone="Tone::Success" role="status">{{ session('status') }}</x-ui.alert>@endif

    <x-ui.card>
        <dl class="grid gap-md md:grid-cols-3">
            <div><dt class="text-caption text-text-subtle">{{ __('invoicing.customer') }}</dt><dd class="font-medium">{{ $invoice->customer_name }}</dd>
                <dd class="text-caption text-text-subtle">{{ $invoice->customer_document_type->label() }} · {{ __('invoicing.masked_document', ['last' => mb_substr($invoice->customer_document_number, -4)]) }}</dd></div>
            <div><dt class="text-caption text-text-subtle">{{ __('invoicing.booking') }}</dt>
                <dd><x-ui.capability-link route="bookings.show" :params="$invoice->booking_ulid" class="font-medium text-brand underline">{{ $invoice->booking_number }}</x-ui.capability-link></dd></div>
            <div><dt class="text-caption text-text-subtle">{{ __('invoicing.issued_at') }}</dt><dd>{{ $invoice->issued_at->timezone($timezone)->locale(app()->getLocale())->isoFormat('lll') }}</dd></div>
            @if ($invoice->related)
                <div><dt class="text-caption text-text-subtle">{{ __('invoicing.related') }}</dt>
                    <dd><a href="{{ route('invoicing.show', $invoice->related->ulid) }}" wire:navigate class="text-brand underline">{{ $invoice->related->number }}</a></dd></div>
            @endif
            @if ($invoice->reason)
                <div class="md:col-span-2"><dt class="text-caption text-text-subtle">{{ __('invoicing.reason') }}</dt><dd>{{ $invoice->reason }}</dd></div>
            @endif
            <div><dt class="text-caption text-text-subtle">{{ __('invoicing.e_invoice') }}</dt>
                <dd><x-ui.badge :tone="$invoice->e_invoice_status->tone()">{{ $invoice->e_invoice_status->label() }}</x-ui.badge>
                    @if ($invoice->e_invoice_reference)<span class="text-caption text-text-subtle">{{ $invoice->e_invoice_reference }}</span>@endif</dd></div>
        </dl>
    </x-ui.card>

    <x-ui.table :caption="__('invoicing.lines_title')">
        <x-slot:head>
            <tr>
                <th scope="col" class="px-md py-sm">{{ __('invoicing.description') }}</th>
                <th scope="col" class="px-md py-sm">{{ __('invoicing.kind') }}</th>
                <th scope="col" class="px-md py-sm">{{ __('invoicing.amount') }}</th>
                <th scope="col" class="px-md py-sm">{{ __('invoicing.tax') }}</th>
            </tr>
        </x-slot:head>
        @foreach ($invoice->lines as $line)
            <tr wire:key="line-{{ $line->id }}">
                <td class="px-md py-sm">{{ $line->description }}</td>
                <td class="px-md py-sm"><x-ui.badge :tone="$line->kind === InvoiceLineKind::OwnIncome ? Tone::Info : Tone::Neutral">{{ $line->kind->label() }}</x-ui.badge></td>
                <td class="px-md py-sm">{{ $presenter->format($invoice->money($line->amount_minor)) }}</td>
                <td class="px-md py-sm">{{ $presenter->format($invoice->money($line->tax_minor)) }}</td>
            </tr>
        @endforeach
    </x-ui.table>

    <x-ui.card :title="__('invoicing.totals')">
        <dl class="grid gap-sm md:grid-cols-4">
            <div><dt class="text-caption text-text-subtle">{{ __('invoicing.third_party') }}</dt><dd>{{ $presenter->format($invoice->money($invoice->third_party_minor)) }}</dd></div>
            <div><dt class="text-caption text-text-subtle">{{ __('invoicing.own_income') }}</dt><dd>{{ $presenter->format($invoice->money($invoice->own_income_minor)) }}</dd></div>
            <div><dt class="text-caption text-text-subtle">{{ __('invoicing.tax') }}</dt><dd>{{ $presenter->format($invoice->money($invoice->tax_minor)) }}</dd></div>
            <div><dt class="text-caption text-text-subtle">{{ __('invoicing.total') }}</dt><dd class="text-heading-3 font-semibold">{{ $presenter->format($invoice->total()) }}</dd></div>
        </dl>
    </x-ui.card>

    @if ($isInvoice)
        <x-ui.card :title="__('invoicing.notes_title')">
            @forelse ($notes as $note)
                <p wire:key="note-{{ $note->ulid }}" class="flex flex-wrap justify-between gap-sm border-b border-border py-xs">
                    <a href="{{ route('invoicing.show', $note->ulid) }}" wire:navigate class="font-medium text-brand underline">{{ $note->type->label() }} {{ $note->number }}</a>
                    <span>{{ $note->type === \App\Modules\Invoicing\Enums\InvoiceType::CreditNote ? '−' : '+' }} {{ $presenter->format($note->total()) }}</span>
                </p>
            @empty
                <p class="text-text-subtle">{{ __('invoicing.no_notes') }}</p>
            @endforelse
            <p class="mt-md flex justify-between font-semibold"><span>{{ __('invoicing.net_total') }}</span><span>{{ $presenter->format($net) }}</span></p>

            @if ($adjustments->isNotEmpty())
                <h3 class="mt-lg font-semibold">{{ __('invoicing.credit_notes.pending_title') }}</h3>
                @foreach ($adjustments as $adjustment)
                    <p wire:key="adjustment-{{ $adjustment->ulid }}" class="flex flex-wrap justify-between gap-sm py-xs">
                        <span>{{ $adjustment->reason }}</span>
                        <span>{{ $presenter->format($invoice->money($adjustment->total_minor)) }} <x-ui.badge :tone="$adjustment->status->tone()">{{ $adjustment->status->label() }}</x-ui.badge></span>
                    </p>
                @endforeach
            @endif
        </x-ui.card>

        <x-ui.card :title="__('invoicing.credit_notes.title')">
            <p class="mb-md text-caption text-text-subtle">{{ __('invoicing.credit_notes.hint') }}</p>
            <form wire:submit="requestCredit" class="flex flex-col gap-md" novalidate>
                @error('credits')<x-ui.alert :tone="Tone::Danger">{{ $message }}</x-ui.alert>@enderror
                <div class="grid gap-md md:grid-cols-2">
                    @foreach ($invoice->lines as $line)
                        <x-ui.field :label="__('invoicing.credit_notes.amount_for', ['line' => $line->description])" for="credits.{{ $line->id }}" :hint="__('invoicing.credit_notes.remaining', ['amount' => $presenter->format($invoice->money($remaining[$line->id] ?? 0))])">
                            <x-ui.input name="credits.{{ $line->id }}" type="number" min="0" step="any" wire:model="credits.{{ $line->id }}" :disabled="($remaining[$line->id] ?? 0) <= 0" hint />
                        </x-ui.field>
                    @endforeach
                </div>
                <x-ui.field :label="__('invoicing.credit_notes.reason')" for="creditReason">
                    <x-ui.input name="creditReason" wire:model="creditReason" required />
                </x-ui.field>
                <div><x-ui.button type="submit" variant="secondary" wire:loading.attr="disabled" wire:target="requestCredit">{{ __('invoicing.credit_notes.request') }}</x-ui.button></div>
            </form>
        </x-ui.card>

        <x-ui.card :title="__('invoicing.debit_notes.title')">
            <p class="mb-md text-caption text-text-subtle">{{ __('invoicing.debit_notes.hint') }}</p>
            <form wire:submit="issueDebit" class="flex flex-col gap-md" novalidate>
                @error('charges')<x-ui.alert :tone="Tone::Danger">{{ $message }}</x-ui.alert>@enderror
                @foreach ($charges as $index => $charge)
                    <div wire:key="charge-{{ $index }}" class="grid gap-sm md:grid-cols-4 md:items-end">
                        <x-ui.field :label="__('invoicing.debit_notes.description')" for="charges.{{ $index }}.description">
                            <x-ui.input name="charges.{{ $index }}.description" wire:model="charges.{{ $index }}.description" required />
                        </x-ui.field>
                        <x-ui.field :label="__('invoicing.debit_notes.kind')" for="charges.{{ $index }}.kind">
                            <x-ui.select name="charges.{{ $index }}.kind" wire:model="charges.{{ $index }}.kind" :options="collect($kinds)->mapWithKeys(fn ($kind) => [$kind->value => $kind->label()])->all()" />
                        </x-ui.field>
                        <x-ui.field :label="__('invoicing.debit_notes.amount') . ' (' . $invoice->currency . ')'" for="charges.{{ $index }}.amount">
                            <x-ui.input name="charges.{{ $index }}.amount" type="number" min="0" step="any" wire:model="charges.{{ $index }}.amount" required />
                        </x-ui.field>
                        @if (count($charges) > 1)
                            <x-ui.button type="button" variant="ghost" wire:click="removeCharge({{ $index }})">{{ __('invoicing.debit_notes.remove_line') }}</x-ui.button>
                        @endif
                    </div>
                @endforeach
                <div><x-ui.button type="button" variant="ghost" wire:click="addCharge">{{ __('invoicing.debit_notes.add_line') }}</x-ui.button></div>
                <x-ui.field :label="__('invoicing.debit_notes.reason')" for="debitReason">
                    <x-ui.input name="debitReason" wire:model="debitReason" required />
                </x-ui.field>
                <div><x-ui.button type="submit" wire:loading.attr="disabled" wire:target="issueDebit" wire:confirm="{{ __('invoicing.confirm_issue', ['booking' => $invoice->booking_number]) }}">{{ __('invoicing.debit_notes.issue') }}</x-ui.button></div>
            </form>
        </x-ui.card>
    @endif

    <div><x-ui.link-button variant="secondary" :href="route('invoicing.index', ['tab' => 'issued'])">{{ __('invoicing.back') }}</x-ui.link-button></div>
</div>
