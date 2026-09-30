<div class="flex flex-col gap-lg">
    <div class="flex flex-col gap-md md:flex-row md:items-end md:justify-between">
        <x-ui.field :label="__('shared.search')" for="search">
            <x-ui.input name="search" type="search" wire:model.live.debounce.400ms="search" />
        </x-ui.field>
        <div class="flex items-center gap-md">
            <x-ui.badge :tone="\App\Modules\Crm\Enums\LeadStatus::Won->tone()">{{ __('crm.leads.won_count', ['count' => $won]) }}</x-ui.badge>
            <x-ui.badge :tone="\App\Modules\Crm\Enums\LeadStatus::Lost->tone()">{{ __('crm.leads.lost_count', ['count' => $lost]) }}</x-ui.badge>
            <x-ui.link-button :href="route('crm.leads.create')">{{ __('crm.leads.create') }}</x-ui.link-button>
        </div>
    </div>

    <div class="grid gap-md md:grid-cols-3" wire:loading.class="opacity-50" wire:target="search">
        @foreach ($columns as $column)
            <section aria-labelledby="column-{{ $column['status']->value }}" class="flex flex-col gap-sm rounded-card bg-muted p-md" wire:key="column-{{ $column['status']->value }}">
                <h2 id="column-{{ $column['status']->value }}" class="flex items-center justify-between text-heading-3 font-semibold">
                    {{ $column['status']->label() }}
                    <x-ui.badge :tone="$column['status']->tone()">{{ $column['total'] }}</x-ui.badge>
                </h2>
                @forelse ($column['leads'] as $lead)
                    <a href="{{ route('crm.leads.show', $lead) }}" wire:navigate wire:key="lead-{{ $lead->ulid }}"
                       class="flex flex-col gap-xs rounded-control border border-border bg-surface p-sm hover:bg-canvas">
                        <span class="font-medium">{{ $lead->contact_name }}</span>
                        <span class="text-caption text-text-subtle">
                            {{ $lead->destination ?? __('crm.leads.no_destination') }}
                            @if ($lead->travel_start) · {{ $lead->travel_start->locale(app()->getLocale())->isoFormat('ll') }} @endif
                            · {{ $lead->channel->label() }}
                        </span>
                    </a>
                @empty
                    <p class="text-caption text-text-subtle">{{ __('crm.leads.empty_column') }}</p>
                @endforelse
            </section>
        @endforeach
    </div>
</div>
