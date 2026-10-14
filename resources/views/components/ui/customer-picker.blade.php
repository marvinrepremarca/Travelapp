{{-- Buscador de clientes común (cotización, expediente directo, factura manual, abonos). El componente Livewire expone customerSearch y chooseCustomer(). --}}
@props(['title', 'error', 'selected' => null, 'matches', 'search' => ''])
<x-ui.card :title="$title">
    <div class="flex flex-col gap-md">
        @if ($selected)
            <p class="font-medium">{{ __('shared.customer_picker.selected', ['name' => $selected->display_name]) }}</p>
        @endif
        <x-ui.field :label="__('shared.customer_picker.search')" for="customerSearch" :hint="__('shared.customer_picker.hint')">
            <x-ui.input name="customerSearch" type="search" wire:model.live.debounce.400ms="customerSearch" hint />
        </x-ui.field>
        @error($error)<p class="text-caption text-danger" role="alert">{{ $message }}</p>@enderror
        @if ($search !== '')
            @if ($matches->isEmpty())
                <p class="text-text-subtle">{{ __('shared.customer_picker.no_matches') }}</p>
            @else
                <ul class="flex flex-col divide-y divide-border">
                    @foreach ($matches as $match)
                        <li class="flex items-center justify-between py-sm" wire:key="match-{{ $match->ulid }}">
                            <span>{{ $match->display_name }}</span>
                            <x-ui.button variant="ghost" type="button" wire:click="chooseCustomer('{{ $match->ulid }}')">
                                {{ __('shared.customer_picker.choose') }}<span class="sr-only"> {{ $match->display_name }}</span>
                            </x-ui.button>
                        </li>
                    @endforeach
                </ul>
            @endif
        @endif
        {{ $slot }}
    </div>
</x-ui.card>
