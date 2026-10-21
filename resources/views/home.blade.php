{{-- Inicio personal: datos de la cuenta y accesos directos a lo que el rol del usuario puede hacer. --}}
@php
    use App\Modules\Shared\Enums\Tone;
    $firstName = \Illuminate\Support\Str::before($user->name, ' ');
@endphp
<x-layouts.backoffice :heading="__('navigation.home_page.title')">
    <div class="flex flex-col gap-lg">
        <x-ui.card>
            <div class="flex flex-col gap-sm md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-heading-2 font-semibold">{{ __('navigation.home_page.greeting', ['name' => $firstName]) }}</p>
                    <p class="text-text-subtle">{{ ucfirst($today->locale(app()->getLocale())->isoFormat('dddd, LL')) }}</p>
                </div>
                @if ($roleLabel)
                    <x-ui.badge :tone="Tone::Info">{{ $roleLabel }}</x-ui.badge>
                @endif
            </div>
        </x-ui.card>

        <div class="grid gap-lg md:grid-cols-3">
            <x-ui.card :title="__('navigation.home_page.account_title')" class="md:col-span-1">
                <dl class="flex flex-col gap-sm">
                    <div>
                        <dt class="text-caption text-text-subtle">{{ __('navigation.home_page.email') }}</dt>
                        <dd class="break-all">{{ $user->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-caption text-text-subtle">{{ __('navigation.home_page.role') }}</dt>
                        <dd>{{ $roleLabel ?? __('navigation.home_page.no_role') }}</dd>
                    </div>
                    <div>
                        <dt class="text-caption text-text-subtle">{{ __('navigation.home_page.branch') }}</dt>
                        <dd>{{ $user->branch?->name ?? __('navigation.home_page.no_branch') }}</dd>
                    </div>
                    <div>
                        <dt class="text-caption text-text-subtle">{{ __('navigation.home_page.scope') }}</dt>
                        <dd>{{ $user->visibility_scope->label() }}</dd>
                        <dd class="text-caption text-text-subtle">{{ __('navigation.home_page.scope_help.' . $user->visibility_scope->value) }}</dd>
                    </div>
                    <div>
                        <dt class="text-caption text-text-subtle">{{ __('navigation.home_page.security') }}</dt>
                        <dd class="flex flex-wrap items-center gap-sm">
                            <x-ui.badge :tone="$user->hasTwoFactorEnabled() ? Tone::Success : Tone::Warning">
                                {{ $user->hasTwoFactorEnabled() ? __('navigation.home_page.two_factor_on') : __('navigation.home_page.two_factor_off') }}
                            </x-ui.badge>
                            <a href="{{ route('identity.security') }}" wire:navigate class="text-caption text-brand underline">{{ __('navigation.home_page.manage_security') }}</a>
                        </dd>
                    </div>
                </dl>
            </x-ui.card>

            <x-ui.card :title="__('navigation.home_page.shortcuts_title')" class="md:col-span-2">
                @if ($stages === [])
                    <x-ui.empty-state :title="__('navigation.home_page.no_shortcuts')" :description="__('navigation.home_page.no_shortcuts_help')" />
                @else
                    <div class="flex flex-col gap-md">
                        @foreach ($stages as $stage)
                            <section aria-labelledby="home-stage-{{ $stage['key'] }}">
                                <h3 id="home-stage-{{ $stage['key'] }}" class="mb-xs text-caption font-semibold uppercase text-text-subtle">{{ __('navigation.stages.' . $stage['key'] . '.title') }}</h3>
                                <ul class="grid gap-sm sm:grid-cols-2">
                                    @foreach ($stage['steps'] as $step)
                                        <li>
                                            <a href="{{ route($step['route']) }}" wire:navigate class="flex h-full flex-col gap-xs rounded-control border border-border bg-surface p-sm hover:bg-muted">
                                                <span class="font-medium text-brand">{{ __("navigation.items.{$step['key']}.label") }}</span>
                                                <span class="text-caption text-text-subtle">{{ __("navigation.items.{$step['key']}.hint") }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </section>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>
        </div>
    </div>
</x-layouts.backoffice>
