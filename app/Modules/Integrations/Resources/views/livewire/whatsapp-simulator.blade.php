@php
    use App\Modules\Communications\Enums\MessageDirection;
    $time = fn ($instant) => $instant->timezone($timezone)->locale(app()->getLocale())->isoFormat('LT');
@endphp
<div class="mx-auto flex w-full max-w-form flex-col gap-md" wire:poll.visible.{{ $pollSeconds }}s>
    <x-ui.alert :tone="\App\Modules\Shared\Enums\Tone::Info">{{ __('integrations.whatsapp.hint') }}</x-ui.alert>

    <div class="grid gap-sm md:grid-cols-2">
        <x-ui.field :label="__('integrations.whatsapp.phone')" for="phone">
            <x-ui.input name="phone" type="tel" wire:model.live.debounce.500ms="phone" />
        </x-ui.field>
        <x-ui.field :label="__('integrations.whatsapp.name')" for="name">
            <x-ui.input name="name" wire:model="name" />
        </x-ui.field>
    </div>

    <ol class="flex min-h-full flex-col gap-sm rounded-card border border-border bg-canvas p-md" aria-label="{{ __('integrations.whatsapp.chat') }}" aria-live="polite">
        @forelse ($lines as $line)
            <li wire:key="line-{{ $line->ulid }}" @class([
                'max-w-prose rounded-card p-sm',
                'self-end bg-success text-brand-contrast' => $line->direction === MessageDirection::Inbound,
                'self-start border border-border bg-surface' => $line->direction === MessageDirection::Outbound,
            ])>
                <p class="whitespace-pre-line">{{ $line->body }}</p>
                <p class="text-caption opacity-75">{{ $line->author->label() }} · {{ $time($line->at) }}</p>
            </li>
        @empty
            <li class="text-text-subtle">{{ __('integrations.whatsapp.empty') }}</li>
        @endforelse
    </ol>

    <form wire:submit="send" class="flex gap-sm" novalidate>
        <div class="flex-1">
            <x-ui.field :label="__('integrations.whatsapp.text')" for="text">
                <x-ui.input name="text" wire:model="text" autocomplete="off" required />
            </x-ui.field>
        </div>
        <div class="self-end"><x-ui.button type="submit" wire:loading.attr="disabled" wire:target="send">{{ __('integrations.whatsapp.send') }}</x-ui.button></div>
    </form>
</div>
