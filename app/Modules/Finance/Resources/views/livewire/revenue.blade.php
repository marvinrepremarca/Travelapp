@php
    use App\Modules\Shared\Enums\Capability;
    use App\Modules\Shared\Enums\Tone;
    $format = fn ($date) => $date->locale(app()->getLocale())->isoFormat('ll');
@endphp
<div class="flex flex-col gap-lg">
    @if (session('status'))<x-ui.alert :tone="Tone::Success" role="status">{{ session('status') }}</x-ui.alert>@endif

    <p class="text-text-subtle">{{ __('finance.revenue.intro') }}</p>

    <div class="flex flex-col gap-md md:flex-row md:items-end">
        <x-ui.field :label="__('finance.revenue.month')" for="month">
            <x-ui.input name="month" type="month" wire:model.live="month" />
        </x-ui.field>
        <x-ui.field :label="__('finance.revenue.source')" for="source">
            <x-ui.select name="source" wire:model.live="source" :placeholder="__('finance.revenue.all_sources')"
                :options="collect($sources)->mapWithKeys(fn ($source) => [$source->value => $source->label()])->all()" />
        </x-ui.field>
    </div>

    <div class="grid gap-md md:grid-cols-3">
        <x-ui.stat :label="__('finance.revenue.recognized')" :value="$presenter->format($recognized)" :hint="__('finance.revenue.recognized_hint')" />
        @capability(Capability::Collections)
            <x-ui.stat :label="__('finance.revenue.collected')" :value="$presenter->format($collected)" :hint="__('finance.revenue.collected_hint')" />
        @endcapability
        @capability(Capability::Invoicing)
            <x-ui.stat :label="__('finance.revenue.invoiced')" :value="$presenter->format($invoiced)" :hint="__('finance.revenue.invoiced_hint')" />
        @endcapability
    </div>

    <div wire:loading.delay wire:target="month,source,gotoPage,nextPage,previousPage"><x-ui.skeleton :lines="4" /></div>

    <div wire:loading.remove wire:target="month,source,gotoPage,nextPage,previousPage">
        @if ($entries->isEmpty())
            <x-ui.empty-state :title="__('finance.revenue.empty')" />
        @else
            <x-ui.table :caption="__('finance.revenue.title')">
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-md py-sm">{{ __('finance.revenue.date') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('finance.revenue.concept') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('finance.revenue.source') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('finance.revenue.amount') }}</th>
                    </tr>
                </x-slot:head>
                @foreach ($entries as $entry)
                    <tr wire:key="revenue-{{ $entry->ulid }}">
                        <td class="px-md py-sm">{{ $format($entry->recognized_on) }}</td>
                        <td class="px-md py-sm">
                            {{ $entry->description }}
                            <p class="text-caption text-text-subtle">{{ collect([$entry->booking_number, $entry->customer_name])->filter()->implode(' · ') }}</p>
                        </td>
                        <td class="px-md py-sm">
                            {{ $entry->source->label() }}
                            <p class="text-caption text-text-subtle">{{ $entry->entry_type->label() }}</p>
                        </td>
                        <td @class(['px-md py-sm font-medium', 'text-danger' => $entry->amount_minor < 0])>{{ $presenter->format($entry->amount()) }}</td>
                    </tr>
                @endforeach
            </x-ui.table>
            <div class="mt-md">{{ $entries->links() }}</div>
        @endif
    </div>

    <x-ui.card :title="__('finance.revenue.manual_title')">
        <p class="mb-md text-text-subtle">{{ __('finance.revenue.manual_help') }}</p>
        <form wire:submit="record" class="grid gap-md md:grid-cols-4 md:items-end" novalidate>
            <x-ui.field :label="__('finance.revenue.fields.manual.description')" for="manual.description">
                <x-ui.input name="manual.description" wire:model="manual.description" />
            </x-ui.field>
            <x-ui.field :label="__('finance.revenue.fields.manual.customer')" for="manual.customer">
                <x-ui.input name="manual.customer" wire:model="manual.customer" />
            </x-ui.field>
            <x-ui.field :label="__('finance.revenue.amount_in', ['currency' => $currency])" for="manual.amount">
                <x-ui.input name="manual.amount" type="number" min="0" step="any" inputmode="decimal" wire:model="manual.amount" />
            </x-ui.field>
            <x-ui.field :label="__('finance.revenue.fields.manual.date')" for="manual.date">
                <x-ui.input name="manual.date" type="date" wire:model="manual.date" />
            </x-ui.field>
            <div class="md:col-span-4">
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="record">{{ __('finance.revenue.manual_submit') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</div>
