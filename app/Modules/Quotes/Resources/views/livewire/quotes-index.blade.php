<div class="flex flex-col gap-lg">
    <div class="flex flex-col gap-md md:flex-row md:items-end md:justify-between">
        <div class="flex flex-col gap-md md:flex-row md:items-end">
            <x-ui.field :label="__('shared.search')" for="search" :hint="__('quotes.search_hint')">
                <x-ui.input name="search" type="search" wire:model.live.debounce.400ms="search" hint />
            </x-ui.field>
            <x-ui.field :label="__('quotes.columns.status')" for="status">
                <x-ui.select name="status" wire:model.live="status" :placeholder="__('quotes.all_statuses')"
                    :options="collect($statuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all()" />
            </x-ui.field>
        </div>
        <x-ui.link-button :href="route('quotes.create')">{{ __('quotes.create') }}</x-ui.link-button>
    </div>

    <div wire:loading.delay wire:target="search,status,gotoPage,nextPage,previousPage"><x-ui.skeleton :lines="4" /></div>

    <div wire:loading.remove wire:target="search,status,gotoPage,nextPage,previousPage">
        @if ($quotes->isEmpty())
            <x-ui.empty-state :title="__('quotes.empty_title')" :description="__('quotes.empty_description')" />
        @else
            <x-ui.table :caption="__('quotes.title')">
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-md py-sm">{{ __('quotes.columns.number') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('quotes.columns.customer') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('quotes.columns.status') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('quotes.columns.valid_until') }}</th>
                    </tr>
                </x-slot:head>
                @foreach ($quotes as $quote)
                    <tr wire:key="quote-{{ $quote->ulid }}">
                        <td class="px-md py-sm">
                            <a href="{{ route('quotes.show', $quote) }}" wire:navigate class="font-medium text-brand underline">{{ $quote->number }}</a>
                            <p class="text-caption text-text-subtle">{{ $quote->title }}</p>
                        </td>
                        <td class="px-md py-sm">{{ $quote->customer->display_name }}</td>
                        <td class="px-md py-sm"><x-ui.badge :tone="$quote->status->tone()">{{ $quote->status->label() }}</x-ui.badge></td>
                        <td class="px-md py-sm">{{ $quote->valid_until?->timezone(config('travel.agency.timezone'))->locale(app()->getLocale())->isoFormat('lll') ?? '—' }}</td>
                    </tr>
                @endforeach
            </x-ui.table>
            <div class="mt-md">{{ $quotes->links() }}</div>
        @endif
    </div>
</div>
