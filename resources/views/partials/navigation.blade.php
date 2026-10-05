{{-- Menú por etapas numeradas en el orden de puesta en marcha (App\Navigation\MainMenu). --}}
@php($stages = app(\App\Navigation\MainMenu::class)->for(auth()->user()))
<div class="mt-md flex flex-col gap-md">
    @php($homeActive = request()->routeIs('dashboard'))
    <a href="{{ route('dashboard') }}" wire:navigate @if ($homeActive) aria-current="page" @endif
       @class(['block rounded-control px-sm py-xs text-body font-medium', 'bg-brand text-brand-contrast' => $homeActive, 'text-text hover:bg-muted' => ! $homeActive])>
        {{ __('navigation.home') }}
    </a>

    @foreach ($stages as $stage)
        <section aria-labelledby="nav-stage-{{ $stage['key'] }}" wire:key="nav-stage-{{ $stage['key'] }}">
            <h2 id="nav-stage-{{ $stage['key'] }}" class="px-sm text-caption font-semibold uppercase text-text-subtle">
                {{ $stage['number'] }}. {{ __("navigation.stages.{$stage['key']}.title") }}
            </h2>
            <ul class="mt-xs flex flex-col gap-xs">
                @foreach ($stage['steps'] as $step)
                    @php($active = request()->routeIs($step['route'] . '*'))
                    <li wire:key="nav-{{ $step['route'] }}">
                        <a href="{{ route($step['route']) }}" wire:navigate title="{{ __("navigation.items.{$step['key']}.hint") }}"
                           @if ($active) aria-current="page" @endif
                           @class([
                               'flex gap-sm rounded-control px-sm py-xs text-body',
                               'bg-brand text-brand-contrast' => $active,
                               'text-text hover:bg-muted' => ! $active,
                           ])>
                            <span @class(['tabular-nums', 'text-text-subtle' => ! $active]) aria-hidden="true">{{ $step['number'] }}</span>
                            <span>{{ __("navigation.items.{$step['key']}.label") }}<span class="sr-only">, {{ __('navigation.step', ['number' => $step['number']]) }}</span></span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endforeach
</div>
