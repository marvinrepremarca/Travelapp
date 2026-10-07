@php use App\Modules\Shared\Enums\Tone; @endphp
<div class="flex flex-col gap-lg">
    @if (session('status'))<x-ui.alert :tone="Tone::Success" role="status">{{ session('status') }}</x-ui.alert>@endif
    @error('calendar')<x-ui.alert :tone="Tone::Danger" role="alert">{{ $message }}</x-ui.alert>@enderror

    <div class="grid gap-md md:grid-cols-3">
        <x-ui.stat :label="__('compliance.overview.documents')" :value="(string) ($counts['expired_documents'] + $counts['expiring_documents'])"
            :hint="__('compliance.overview.documents_hint', ['expired' => $counts['expired_documents'], 'expiring' => $counts['expiring_documents'], 'days' => config('travel.compliance.document_alert_days')])"
            :tone="$counts['expired_documents'] > 0 ? 'danger' : ($counts['expiring_documents'] > 0 ? 'warning' : null)" :href="route('compliance.documents')" />
        <x-ui.stat :label="__('compliance.overview.obligations')" :value="(string) ($counts['overdue_obligations'] + $counts['due_obligations'])"
            :hint="__('compliance.overview.obligations_hint', ['overdue' => $counts['overdue_obligations'], 'due' => $counts['due_obligations'], 'days' => config('travel.compliance.obligation_alert_days')])"
            :tone="$counts['overdue_obligations'] > 0 ? 'danger' : ($counts['due_obligations'] > 0 ? 'warning' : null)" />
        <x-ui.stat :label="__('compliance.overview.requests')" :value="(string) ($counts['overdue_requests'] + $counts['due_requests'])"
            :hint="__('compliance.overview.requests_hint', ['overdue' => $counts['overdue_requests'], 'due' => $counts['due_requests'], 'days' => config('travel.compliance.request_alert_business_days')])"
            :tone="$counts['overdue_requests'] > 0 ? 'danger' : ($counts['due_requests'] > 0 ? 'warning' : null)" :href="route('compliance.requests')" />
    </div>

    <div class="flex flex-wrap gap-sm">
        <x-ui.link-button :href="route('compliance.obligations.create')">{{ __('compliance.obligations.create') }}</x-ui.link-button>
        <x-ui.link-button variant="secondary" :href="route('compliance.documents.create')">{{ __('compliance.documents.create') }}</x-ui.link-button>
        <x-ui.link-button variant="secondary" :href="route('compliance.requests.create')">{{ __('compliance.requests.create') }}</x-ui.link-button>
    </div>

    <x-ui.card :title="__('compliance.calendar.title', ['days' => config('travel.compliance.calendar_days')])">
        @forelse ($calendar as $index => $entry)
            @php($overdue = $entry['date']->lt($today))
            <div wire:key="calendar-{{ $entry['kind'] }}-{{ $index }}" class="flex flex-col gap-xs border-b border-border py-sm md:flex-row md:items-center md:justify-between">
                <div class="flex flex-col">
                    <span class="flex flex-wrap items-center gap-sm">
                        <x-ui.badge :tone="$overdue ? Tone::Danger : Tone::Info">{{ $entry['date']->locale(app()->getLocale())->isoFormat('ll') }}</x-ui.badge>
                        <a href="{{ $entry['href'] }}" wire:navigate class="font-medium text-brand underline">{{ $entry['title'] }}</a>
                        @if ($overdue)<span class="text-caption text-danger">{{ __('compliance.calendar.overdue') }}</span>@endif
                    </span>
                    <span class="text-caption text-text-subtle">{{ __('compliance.calendar.' . $entry['kind']) }} · {{ __('compliance.calendar.responsible', ['name' => $entry['responsible']]) }}</span>
                </div>
                @if ($entry['obligation'])
                    <x-ui.button type="button" variant="secondary" wire:click="complete('{{ $entry['obligation']->ulid }}')" wire:loading.attr="disabled" wire:target="complete">{{ __('compliance.obligations.complete') }}</x-ui.button>
                @endif
            </div>
        @empty
            <x-ui.empty-state :title="__('compliance.calendar.empty')" />
        @endforelse
    </x-ui.card>
</div>
