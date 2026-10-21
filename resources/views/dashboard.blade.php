{{-- Inicio de quien administra la plataforma: estado de las capacidades del sistema (ADR-0007). --}}
<x-layouts.backoffice :heading="__('capabilities.guide.title')">
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
                <p class="text-caption text-text-subtle">{{ __('capabilities.guide.how_to') }}</p>
            </div>
        </x-ui.card>
</x-layouts.backoffice>
