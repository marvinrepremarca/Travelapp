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
                <dd><a href="{{ route('bookings.show', $invoice->booking_ulid) }}" wire:navigate class="font-medium text-brand underline">{{ $invoice->booking_number }}</a></dd></div>
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

    <div><x-ui.link-button variant="secondary" :href="route('invoicing.index', ['tab' => 'issued'])">{{ __('invoicing.back') }}</x-ui.link-button></div>
</div>
