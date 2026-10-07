<div class="mx-auto flex w-full max-w-form flex-col gap-lg">
    <header class="flex flex-col gap-xs">
        <h1 class="text-heading-1 font-semibold">{{ __('portal.access.title') }}</h1>
        <p class="text-text-subtle">{{ __('portal.access.hint') }}</p>
    </header>

    @if ($requested)
        <x-ui.alert :tone="\App\Modules\Shared\Enums\Tone::Success" role="status">{{ __('portal.access.sent') }}</x-ui.alert>
    @endif

    <x-ui.card>
        <form wire:submit="send" class="flex flex-col gap-md" novalidate>
            <x-ui.field :label="__('portal.access.booking_number')" for="bookingNumber" :hint="__('portal.access.booking_hint')">
                <x-ui.input name="bookingNumber" wire:model="bookingNumber" autocomplete="off" required hint />
            </x-ui.field>
            <x-ui.field :label="__('portal.access.document_number')" for="documentNumber">
                <x-ui.input name="documentNumber" wire:model="documentNumber" autocomplete="off" inputmode="numeric" required />
            </x-ui.field>
            <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="send">{{ __('portal.access.send') }}</x-ui.button>
        </form>
    </x-ui.card>
</div>
