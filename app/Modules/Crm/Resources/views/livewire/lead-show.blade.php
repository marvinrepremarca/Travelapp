@php
    use App\Modules\Crm\Enums\LeadStatus;
@endphp
<div class="grid gap-lg lg:grid-cols-3">
    <div class="flex flex-col gap-lg lg:col-span-2">
        <x-ui.card>
            <div class="flex flex-col gap-md">
                <div class="flex flex-wrap items-center gap-sm">
                    <x-ui.badge :tone="$lead->status->tone()">{{ $lead->status->label() }}</x-ui.badge>
                    <span class="text-caption text-text-subtle">{{ $lead->channel->label() }} · {{ __('crm.leads.owner', ['name' => $users[$lead->owner_id] ?? '—']) }}</span>
                </div>
                <dl class="grid gap-md md:grid-cols-2">
                    <div><dt class="text-caption text-text-subtle">{{ __('crm.leads.fields.email') }}</dt><dd>{{ $lead->email ?? '—' }}</dd></div>
                    <div><dt class="text-caption text-text-subtle">{{ __('crm.leads.fields.phone') }}</dt><dd>{{ $lead->phone ?? '—' }}</dd></div>
                    <div><dt class="text-caption text-text-subtle">{{ __('crm.leads.fields.destination') }}</dt><dd>{{ $lead->destination ?? '—' }}</dd></div>
                    <div><dt class="text-caption text-text-subtle">{{ __('crm.leads.fields.travelers_count') }}</dt><dd>{{ $lead->travelers_count ?? '—' }}</dd></div>
                    <div>
                        <dt class="text-caption text-text-subtle">{{ __('crm.leads.dates') }}</dt>
                        <dd>{{ $lead->travel_start?->locale(app()->getLocale())->isoFormat('ll') ?? '—' }} – {{ $lead->travel_end?->locale(app()->getLocale())->isoFormat('ll') ?? '—' }}</dd>
                    </div>
                    @if ($lead->customer)
                        <div>
                            <dt class="text-caption text-text-subtle">{{ __('crm.leads.customer') }}</dt>
                            <dd><a href="{{ route('crm.customers.show', $lead->customer) }}" wire:navigate class="text-brand underline">{{ $lead->customer->display_name }}</a></dd>
                        </div>
                    @endif
                    @if ($lead->lost_reason)
                        <div>
                            <dt class="text-caption text-text-subtle">{{ __('crm.leads.fields.lost_reason') }}</dt>
                            <dd>{{ $lead->lost_reason->label() }} {{ $lead->lost_note }}</dd>
                        </div>
                    @endif
                </dl>
                @if ($lead->notes)
                    <p class="text-text-subtle">{{ $lead->notes }}</p>
                @endif
                <div class="flex justify-end">
                    <x-ui.link-button :href="route('crm.leads.edit', $lead)" variant="secondary">{{ __('shared.edit') }}</x-ui.link-button>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card :title="__('crm.leads.interactions')">
            <form wire:submit="logInteraction" class="mb-md flex flex-col gap-md md:flex-row md:items-end" novalidate>
                <x-ui.field :label="__('crm.leads.fields.interaction_type')" for="interactionType">
                    <x-ui.select name="interactionType" wire:model="interactionType"
                        :options="collect($interactionTypes)->mapWithKeys(fn ($type) => [$type->value => $type->label()])->all()" />
                </x-ui.field>
                <x-ui.field :label="__('crm.leads.fields.interaction_summary')" for="interactionSummary" class="flex-1">
                    <x-ui.input name="interactionSummary" wire:model="interactionSummary" required />
                </x-ui.field>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="logInteraction">{{ __('crm.leads.log_interaction') }}</x-ui.button>
            </form>
            @if ($lead->interactions->isEmpty())
                <x-ui.empty-state :title="__('crm.leads.no_interactions')" />
            @else
                <ol class="flex flex-col gap-sm">
                    @foreach ($lead->interactions as $interaction)
                        <li class="rounded-control border border-border p-sm" wire:key="interaction-{{ $interaction->id }}">
                            <p class="text-caption text-text-subtle">
                                {{ $interaction->type->label() }} · {{ $interaction->occurred_at->locale(app()->getLocale())->isoFormat('lll') }} · {{ $users[$interaction->user_id] ?? '—' }}
                            </p>
                            <p>{{ $interaction->summary }}</p>
                        </li>
                    @endforeach
                </ol>
            @endif
        </x-ui.card>
    </div>

    <div class="flex flex-col gap-lg">
        @if ($nextSteps !== [])
            <x-ui.card :title="__('crm.leads.advance')">
                @error('status') <x-ui.alert :tone="\App\Modules\Shared\Enums\Tone::Danger" class="mb-sm">{{ $message }}</x-ui.alert> @enderror
                <div class="flex flex-wrap gap-sm">
                    @foreach ($nextSteps as $next)
                        <x-ui.button wire:click="move('{{ $next->value }}')" wire:loading.attr="disabled" wire:key="move-{{ $next->value }}">
                            {{ __('crm.leads.move_to', ['status' => $next->label()]) }}
                        </x-ui.button>
                    @endforeach
                </div>
            </x-ui.card>
        @endif

        @if ($canWin)
            <x-ui.card :title="__('crm.leads.win_title')">
                <div class="flex flex-col gap-md">
                    <x-ui.field :label="__('crm.leads.customer')" for="customerUlid" :hint="__('crm.leads.win_hint')">
                        <x-ui.select name="customerUlid" wire:model="customerUlid" :options="$customers" :placeholder="__('crm.leads.choose_customer')" hint />
                    </x-ui.field>
                    <x-ui.link-button :href="route('crm.customers.create')" variant="secondary">{{ __('crm.customers.create') }}</x-ui.link-button>
                    <x-ui.button wire:click="move('{{ LeadStatus::Won->value }}')" wire:loading.attr="disabled">{{ __('crm.leads.mark_won') }}</x-ui.button>
                </div>
            </x-ui.card>
        @endif

        @if ($canLose)
            <x-ui.card :title="__('crm.leads.lose_title')">
                <div class="flex flex-col gap-md">
                    <x-ui.field :label="__('crm.leads.fields.lost_reason')" for="lostReason">
                        <x-ui.select name="lostReason" wire:model="lostReason" :placeholder="__('crm.leads.choose_reason')"
                            :options="collect($lostReasons)->mapWithKeys(fn ($reason) => [$reason->value => $reason->label()])->all()" />
                    </x-ui.field>
                    <x-ui.field :label="__('crm.leads.fields.lost_note')" for="lostNote">
                        <x-ui.input name="lostNote" wire:model="lostNote" />
                    </x-ui.field>
                    <x-ui.button variant="danger" wire:click="move('{{ LeadStatus::Lost->value }}')" wire:loading.attr="disabled">{{ __('crm.leads.mark_lost') }}</x-ui.button>
                </div>
            </x-ui.card>
        @endif
    </div>
</div>
