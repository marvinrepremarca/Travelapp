@php($stages = app(\App\Navigation\MainMenu::class)->for(auth()->user()))
<x-layouts.backoffice :heading="__('navigation.guide.title')">
    <div class="flex flex-col gap-lg">
        <p class="text-text-subtle">{{ __('navigation.guide.intro') }}</p>

        <x-ui.card :title="__('navigation.guide.flow_title')">
            <p>{{ __('navigation.guide.flow') }}</p>
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
