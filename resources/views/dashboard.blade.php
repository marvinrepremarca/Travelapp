@php($stages = app(\App\Navigation\MainMenu::class)->for(auth()->user()))
@php($capabilityStates = app(\App\Modules\Shared\Capabilities\Capabilities::class))
@php($capabilities = \App\Modules\Shared\Enums\Capability::cases())
<x-layouts.backoffice :heading="__('navigation.guide.title')">
    <div class="flex flex-col gap-lg">
        <p class="text-text-subtle">{{ __('navigation.guide.intro') }}</p>

        <x-ui.card :title="__('navigation.guide.flow_title')">
            <p>{{ __('navigation.guide.flow') }}</p>
        </x-ui.card>

        <x-ui.card :title="__('capabilities.guide.title')">
            <div class="flex flex-col gap-md">
                <p>{{ __('capabilities.guide.intro') }}</p>
                <ul class="flex flex-col divide-y divide-border" aria-label="{{ __('capabilities.guide.list_label') }}">
                    <li class="flex flex-wrap items-start justify-between gap-sm py-sm">
                        <div>
                            <p class="font-medium">{{ __('capabilities.guide.core_title') }}</p>
                            <p class="text-caption text-text-subtle">{{ __('capabilities.guide.core') }}</p>
                        </div>
                        <x-ui.badge :tone="\App\Modules\Shared\Enums\Tone::Info">{{ __('capabilities.guide.always_on') }}</x-ui.badge>
                    </li>
                    @foreach ($capabilities as $capability)
                        @php($isOn = $capabilityStates->enabled($capability))
                        <li class="flex flex-wrap items-start justify-between gap-sm py-sm">
                            <div>
                                <p class="font-medium">{{ $capability->label() }}</p>
                                <p class="text-caption text-text-subtle">{{ __("capabilities.descriptions.{$capability->value}") }}</p>
                            </div>
                            <x-ui.badge :tone="$isOn ? \App\Modules\Shared\Enums\Tone::Success : \App\Modules\Shared\Enums\Tone::Neutral">
                                {{ __($isOn ? 'capabilities.status.on' : 'capabilities.status.off') }}
                            </x-ui.badge>
                        </li>
                    @endforeach
                </ul>
                <div>
                    <h3 class="font-semibold">{{ __('capabilities.guide.progress_title') }}</h3>
                    <ul class="mt-xs flex list-disc flex-col gap-xs pl-lg">
                        @foreach (__('capabilities.guide.progress') as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
                <p class="text-caption text-text-subtle">{{ __('capabilities.guide.how_to') }}</p>
            </div>
        </x-ui.card>
        @foreach ($stages as $stage)
            <x-ui.card :title="$stage['number'] . '. ' . __('navigation.stages.' . $stage['key'] . '.title')">
                <p class="mb-md text-text-subtle">{{ __("navigation.stages.{$stage['key']}.description") }}</p>
                <ol class="flex flex-col divide-y divide-border">
                    @foreach ($stage['steps'] as $step)
                        <li class="flex flex-wrap items-start justify-between gap-sm py-sm">
                            <div class="flex gap-md">
                                <span class="font-semibold tabular-nums text-brand">{{ $step['number'] }}</span>
                                <div>
                                    <p class="font-medium">{{ __("navigation.items.{$step['key']}.label") }}</p>
                                    <p class="text-caption text-text-subtle">{{ __("navigation.items.{$step['key']}.hint") }}</p>
                                </div>
                            </div>
                            <x-ui.link-button variant="secondary" :href="route($step['route'])">
                                {{ __('navigation.guide.open') }}<span class="sr-only"> {{ __("navigation.items.{$step['key']}.label") }}</span>
                            </x-ui.link-button>
                        </li>
                    @endforeach
                </ol>
            </x-ui.card>
        @endforeach
    </div>
</x-layouts.backoffice>
