<form wire:submit="save" class="flex flex-col gap-lg" novalidate>
    <p class="text-text-subtle">{{ __('bookings.direct.intro') }}</p>

    <x-ui.card :title="__('bookings.direct.fields.customerUlid')">
        <div class="flex flex-col gap-md">
            @if ($selected)
                <p class="font-medium">{{ __('bookings.direct.customer_selected', ['name' => $selected->display_name]) }}</p>
            @endif
            <x-ui.field :label="__('bookings.direct.customer_search')" for="customerSearch">
                <x-ui.input name="customerSearch" type="search" wire:model.live.debounce.400ms="customerSearch" />
            </x-ui.field>
            @error('customerUlid')<p class="text-caption text-danger" role="alert">{{ $message }}</p>@enderror
            @if ($customerSearch !== '')
                @if ($matches->isEmpty())
                    <p class="text-text-subtle">{{ __('bookings.direct.no_matches') }}</p>
                @else
                    <ul class="flex flex-col divide-y divide-border">
                        @foreach ($matches as $match)
                            <li wire:key="customer-{{ $match->ulid }}">
                                <button type="button" class="w-full py-xs text-left text-brand underline" wire:click="chooseCustomer('{{ $match->ulid }}')">{{ $match->display_name }}</button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            @endif
        </div>
    </x-ui.card>

    <x-ui.field :label="__('bookings.direct.fields.title')" for="title">
        <x-ui.input name="title" wire:model="title" />
    </x-ui.field>

    <div>
        <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">{{ __('bookings.direct.submit') }}</x-ui.button>
    </div>
</form>
