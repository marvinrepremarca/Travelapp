@php
    use App\Modules\Communications\Enums\ConversationStatus;
    use App\Modules\Communications\Enums\MessageDirection;
    use App\Modules\Shared\Enums\Tone;
    $time = fn ($instant) => $instant->timezone($timezone)->locale(app()->getLocale())->isoFormat('lll');
@endphp
<div class="grid gap-lg md:grid-cols-3" wire:poll.visible.{{ $pollSeconds }}s>
    <section class="flex flex-col gap-md" aria-labelledby="conversations-heading">
        <h2 id="conversations-heading" class="sr-only">{{ __('communications.inbox_title') }}</h2>
        <div class="flex flex-wrap gap-xs" role="group" aria-label="{{ __('communications.filter') }}">
            @foreach ($filters as $option)
                <x-ui.button type="button" :variant="$filter === $option ? 'primary' : 'secondary'" :aria-pressed="$filter === $option ? 'true' : 'false'" wire:click="$set('filter', '{{ $option }}')">
                    {{ __('communications.filters.' . $option) }}
                </x-ui.button>
            @endforeach
        </div>

        @if ($conversations->isEmpty())
            <x-ui.empty-state :title="__('communications.empty')" :description="__('communications.empty_hint')" />
        @else
            <ul class="flex flex-col divide-y divide-border rounded-card border border-border bg-surface">
                @foreach ($conversations as $item)
                    <li wire:key="conversation-{{ $item->ulid }}">
                        <button type="button" wire:click="select('{{ $item->ulid }}')" @class(['flex w-full flex-col gap-xs p-sm text-left hover:bg-muted', 'bg-muted' => $selected?->ulid === $item->ulid]) aria-current="{{ $selected?->ulid === $item->ulid ? 'true' : 'false' }}">
                            <span class="flex items-center justify-between gap-sm">
                                <span class="font-medium">{{ $item->contact_name ?? __('communications.unknown_contact') }}</span>
                                <x-ui.badge :tone="$item->status->tone()">{{ $item->status->label() }}</x-ui.badge>
                            </span>
                            <span class="text-caption text-text-subtle">{{ $phones->mask($item->contact_phone) }} · {{ $time($item->last_message_at) }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="flex flex-col gap-md md:col-span-2" aria-live="polite">
        @error('conversation')<x-ui.alert :tone="Tone::Danger">{{ $message }}</x-ui.alert>@enderror

        @if (! $selected)
            <x-ui.empty-state :title="__('communications.select_title')" :description="__('communications.select_hint')" />
        @else
            <x-ui.card>
                <div class="flex flex-wrap items-start justify-between gap-sm">
                    <div>
                        <p class="text-heading-3 font-semibold">{{ $selected->contact_name ?? __('communications.unknown_contact') }}</p>
                        <p class="text-caption text-text-subtle">{{ $phones->mask($selected->contact_phone) }} · {{ $selected->status->label() }}</p>
                    </div>
                    <div class="flex flex-wrap gap-sm">
                        @if ($selected->owner_id === null && $selected->status->isOpen())
                            <x-ui.button type="button" wire:click="take" wire:loading.attr="disabled" wire:target="take">{{ __('communications.take') }}</x-ui.button>
                        @endif
                        @if ($selected->lead_ulid)
                            <x-ui.link-button variant="secondary" :href="route('crm.leads.show', $selected->lead_ulid)">{{ __('communications.open_lead') }}</x-ui.link-button>
                        @endif
                        @if ($selected->status->isOpen() && ($selected->owner_id === null || $selected->owner_id === $actor->id))
                            <x-ui.button type="button" variant="ghost" wire:click="close" wire:confirm="{{ __('communications.confirm_close') }}" wire:loading.attr="disabled" wire:target="close">{{ __('communications.close') }}</x-ui.button>
                        @endif
                    </div>
                </div>

                <dl class="mt-md grid gap-sm md:grid-cols-3">
                    @foreach ($steps as $step)
                        <div><dt class="text-caption text-text-subtle">{{ __('communications.bot.field.' . $step->value) }}</dt>
                            <dd>{{ $selected->bot_data[$step->value] ?? '—' }}</dd></div>
                    @endforeach
                </dl>
            </x-ui.card>

            <ol class="flex flex-col gap-sm" aria-label="{{ __('communications.messages') }}">
                @foreach ($selected->messages as $message)
                    <li wire:key="message-{{ $message->ulid }}" @class([
                        'max-w-prose rounded-card p-sm',
                        'self-start border border-border bg-surface' => $message->direction === MessageDirection::Inbound,
                        'self-end bg-muted' => $message->direction === MessageDirection::Outbound,
                    ])>
                        <p class="text-caption font-medium text-text-subtle">{{ $message->author->label() }} · {{ $time($message->sent_at) }}</p>
                        <p class="whitespace-pre-line">{{ $message->body }}</p>
                        @if ($message->direction === MessageDirection::Outbound)
                            <p class="text-caption text-text-subtle">{{ $message->status->label() }}</p>
                        @endif
                    </li>
                @endforeach
            </ol>

            @if ($selected->owner_id === $actor->id && $selected->status === ConversationStatus::WithAgent)
                <form wire:submit="send" class="flex flex-col gap-sm" novalidate>
                    <x-ui.field :label="__('communications.reply')" for="reply">
                        <x-ui.input name="reply" wire:model="reply" autocomplete="off" required />
                    </x-ui.field>
                    <div><x-ui.button type="submit" wire:loading.attr="disabled" wire:target="send">{{ __('communications.send') }}</x-ui.button></div>
                </form>
            @elseif ($selected->status->isOpen() && $selected->owner_id === null)
                <p class="text-caption text-text-subtle">{{ __('communications.take_hint') }}</p>
            @endif
        @endif
    </section>
</div>
