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

    {{-- Tablero kanban: una columna por etapa; en pantallas pequeñas las columnas se desplazan en horizontal. --}}
    <div class="flex snap-x gap-md overflow-x-auto pb-sm md:grid md:grid-cols-3 md:items-start md:overflow-visible" wire:loading.class="opacity-50" wire:target="search,showMore">
        @foreach ($columns as $column)
            <section aria-labelledby="stage-{{ $column['status']->value }}" class="flex w-full shrink-0 snap-start flex-col gap-sm rounded-card bg-muted p-md md:shrink" wire:key="stage-{{ $column['status']->value }}">
                <h2 id="stage-{{ $column['status']->value }}" class="flex items-center justify-between gap-sm text-heading-3 font-semibold">
                    {{ $column['status']->label() }}
                    <x-ui.badge :tone="$column['status']->tone()">{{ $column['total'] }}</x-ui.badge>
                </h2>
                @forelse ($column['leads'] as $lead)
                    <a href="{{ route('crm.leads.show', $lead) }}" wire:navigate wire:key="lead-{{ $lead->ulid }}"
                       class="flex flex-col gap-xs rounded-control border border-border bg-surface p-sm hover:bg-canvas">
                        <span class="font-medium">{{ $lead->contact_name }}</span>
                        <span class="text-caption text-text-subtle"><span class="sr-only">{{ __('crm.leads.destination') }}: </span>{{ $lead->destination ?? __('crm.leads.no_destination') }}</span>
                        <span class="text-caption text-text-subtle">
                            @if ($lead->travel_start)<span class="sr-only">{{ __('crm.leads.travel_start') }}: </span>{{ $lead->travel_start->locale(app()->getLocale())->isoFormat('ll') }} · @endif
                            <span class="sr-only">{{ __('crm.leads.channel') }}: </span>{{ $lead->channel->label() }}
                        </span>
                    </a>
                @empty
                    <p class="text-caption text-text-subtle">{{ __('crm.leads.empty_column') }}</p>
                @endforelse
                @if ($column['leads']->isNotEmpty())
                    <p class="text-caption text-text-subtle">{{ __('crm.leads.showing', ['shown' => $column['leads']->count(), 'total' => $column['total']]) }}</p>
                    @if ($column['leads']->count() < $column['total'])
                        <x-ui.button type="button" variant="secondary" wire:click="showMore('{{ $column['status']->value }}')" wire:loading.attr="disabled" wire:target="showMore">
                            {{ __('crm.leads.show_more', ['stage' => $column['status']->label()]) }}
                        </x-ui.button>
                    @endif
                @endif
            </section>
        @endforeach
    </div>
</div>
