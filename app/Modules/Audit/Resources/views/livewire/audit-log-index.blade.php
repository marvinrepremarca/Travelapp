@php
    use App\Modules\Audit\Enums\AuditTab;
    use App\Modules\Audit\Enums\SecurityEvent;
@endphp
<div class="flex flex-col gap-lg">
    <div role="tablist" aria-label="{{ __('audit.title') }}" class="flex flex-wrap gap-sm">
        @foreach ($tabs as $tabOption)
            <x-ui.button role="tab" wire:key="tab-{{ $tabOption->value }}"
                :variant="$currentTab === $tabOption ? 'primary' : 'secondary'"
                aria-selected="{{ $currentTab === $tabOption ? 'true' : 'false' }}"
                wire:click="$set('tab', '{{ $tabOption->value }}')">
                {{ $tabOption->label() }}
            </x-ui.button>
        @endforeach
    </div>

    <div class="flex flex-col gap-md md:flex-row">
        @if ($currentTab === AuditTab::Changes)
            <x-ui.field :label="__('audit.filters.module')" for="logName">
                <x-ui.select name="logName" wire:model.live="logName" :placeholder="__('audit.filters.all_modules')"
                    :options="collect($logNames)->mapWithKeys(fn ($name) => [$name->value => $name->label()])->all()" />
            </x-ui.field>
        @endif
        <x-ui.field :label="__('audit.filters.from')" for="from">
            <x-ui.input name="from" type="date" wire:model.live="from" />
        </x-ui.field>
        <x-ui.field :label="__('audit.filters.to')" for="to">
            <x-ui.input name="to" type="date" wire:model.live="to" />
        </x-ui.field>
    </div>

    <div wire:loading.delay wire:target="tab,logName,from,to,gotoPage,nextPage,previousPage"><x-ui.skeleton :lines="4" /></div>

    <div role="tabpanel" wire:loading.remove wire:target="tab,logName,from,to,gotoPage,nextPage,previousPage">
        @if ($entries->isEmpty())
            <x-ui.empty-state :title="__('audit.empty_title')" :description="__('audit.empty_description')" />
        @elseif ($currentTab === AuditTab::SensitiveAccess)
            <x-ui.table :caption="$currentTab->label()">
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-md py-sm">{{ __('audit.columns.when') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('audit.columns.user') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('audit.columns.subject') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('audit.columns.field') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('audit.columns.action') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('audit.columns.reason') }}</th>
                    </tr>
                </x-slot:head>
                @foreach ($entries as $access)
                    <tr wire:key="access-{{ $access->id }}">
                        <td class="px-md py-sm">{{ $access->accessed_at->setTimezone($timezone)->locale(app()->getLocale())->isoFormat('lll') }}</td>
                        <td class="px-md py-sm">#{{ $access->user_id }}</td>
                        <td class="px-md py-sm">{{ class_basename($access->subject_type) }} #{{ $access->subject_id }}</td>
                        <td class="px-md py-sm">{{ $access->field }}</td>
                        <td class="px-md py-sm"><x-ui.badge :tone="$access->type->tone()">{{ $access->type->label() }}</x-ui.badge></td>
                        <td class="px-md py-sm">{{ $access->reason }}</td>
                    </tr>
                @endforeach
            </x-ui.table>
        @else
            <x-ui.table :caption="$currentTab->label()">
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-md py-sm">{{ __('audit.columns.when') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('audit.columns.user') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('audit.columns.action') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('audit.columns.detail') }}</th>
                    </tr>
                </x-slot:head>
                @foreach ($entries as $activity)
                    @php($securityEvent = $currentTab === AuditTab::Security ? SecurityEvent::tryFrom((string) $activity->event) : null)
                    <tr wire:key="activity-{{ $activity->id }}">
                        <td class="px-md py-sm">{{ $activity->created_at?->setTimezone($timezone)->locale(app()->getLocale())->isoFormat('lll') }}</td>
                        <td class="px-md py-sm">{{ $activity->causer?->name ?? __('audit.system') }}</td>
                        <td class="px-md py-sm">
                            @if ($securityEvent)
                                <x-ui.badge :tone="$securityEvent->tone()">{{ $securityEvent->label() }}</x-ui.badge>
                            @else
                                {{ __('audit.events.'.($activity->event ?? 'updated')) }} · {{ class_basename((string) $activity->subject_type) }} #{{ $activity->subject_id }}
                            @endif
                        </td>
                        <td class="px-md py-sm text-caption">
                            @if ($securityEvent)
                                {{ $activity->getExtraProperty('identifier') }} {{ $activity->getExtraProperty('ip') }}
                            @else
                                <dl class="grid grid-cols-1 gap-xs">
                                    @foreach ((array) $activity->getExtraProperty('attributes') as $field => $value)
                                        <div wire:key="change-{{ $activity->id }}-{{ $field }}">
                                            <dt class="inline font-medium">{{ $field }}:</dt>
                                            <dd class="inline">
                                                @if (array_key_exists($field, (array) $activity->getExtraProperty('old')))
                                                    <span class="text-text-subtle line-through">{{ json_encode(((array) $activity->getExtraProperty('old'))[$field], JSON_UNESCAPED_UNICODE) }}</span>
                                                    <span aria-hidden="true">→</span><span class="sr-only">{{ __('audit.changed_to') }}</span>
                                                @endif
                                                {{ json_encode($value, JSON_UNESCAPED_UNICODE) }}
                                            </dd>
                                        </div>
                                    @endforeach
                                </dl>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
        @endif
        <div class="mt-md">{{ $entries->links() }}</div>
    </div>
</div>
