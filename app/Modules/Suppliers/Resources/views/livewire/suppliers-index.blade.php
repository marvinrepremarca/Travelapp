<div class="flex flex-col gap-lg">
    <div class="flex flex-col gap-md md:flex-row md:items-end md:justify-between">
        <div class="flex flex-col gap-md md:flex-row md:items-end">
            <x-ui.field :label="__('shared.search')" for="search" :hint="__('suppliers.search_hint')">
                <x-ui.input name="search" type="search" wire:model.live.debounce.400ms="search" hint />
            </x-ui.field>
            <label class="flex items-center gap-sm text-body">
                <input type="checkbox" wire:model.live="onlyAttention" class="rounded-control border-border">
                {{ __('suppliers.only_attention') }}
            </label>
        </div>
        @if ($canManage)
            <x-ui.link-button :href="route('suppliers.create')">{{ __('suppliers.create') }}</x-ui.link-button>
        @endif
    </div>

    <div wire:loading.delay wire:target="search,onlyAttention,gotoPage,nextPage,previousPage"><x-ui.skeleton :lines="4" /></div>

    <div wire:loading.remove wire:target="search,onlyAttention,gotoPage,nextPage,previousPage">
        @if ($suppliers->isEmpty())
            <x-ui.empty-state :title="__('suppliers.empty_title')" :description="__('suppliers.empty_description')" />
        @else
            <x-ui.table :caption="__('suppliers.title')">
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-md py-sm">{{ __('suppliers.fields.trade_name') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('suppliers.fields.tax_id') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('suppliers.fields.payment_terms') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('suppliers.standing_column') }}</th>
                    </tr>
                </x-slot:head>
                @foreach ($suppliers as $supplier)
                    @php($standing = $supplier->standingOn($today, $warningDays))
                    <tr wire:key="supplier-{{ $supplier->ulid }}">
                        <td class="px-md py-sm">
                            <a href="{{ route('suppliers.show', $supplier) }}" wire:navigate class="font-medium text-brand underline">{{ $supplier->trade_name }}</a>
                            <p class="text-caption text-text-subtle">{{ $supplier->legal_name }} · {{ $supplier->country }}</p>
                        </td>
                        <td class="px-md py-sm">{{ $supplier->tax_id }}</td>
                        <td class="px-md py-sm">{{ __('suppliers.terms_summary', ['terms' => $supplier->payment_terms->label(), 'days' => $supplier->payment_days, 'currency' => $supplier->payment_currency]) }}</td>
                        <td class="px-md py-sm"><x-ui.badge :tone="$standing->tone()">{{ $standing->label() }}</x-ui.badge></td>
                    </tr>
                @endforeach
            </x-ui.table>
            <div class="mt-md">{{ $suppliers->links() }}</div>
        @endif
    </div>
</div>
