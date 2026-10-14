<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body>
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:p-sm">{{ __('shared.skip_to_content') }}</a>
    <div class="flex min-h-screen flex-col md:flex-row">
        {{-- En móvil el menú arranca plegado y se abre con el botón hamburguesa; desde md siempre está visible. --}}
        <nav aria-label="{{ __('shared.main_navigation') }}" x-data="{ open: false }" class="border-b border-border bg-surface p-md md:w-sidebar md:border-b-0 md:border-r">
            <div class="flex items-center justify-between gap-sm">
                <p class="text-heading-3 font-semibold text-brand">{{ $agencyName ?? config('app.name') }}</p>
                <button type="button" class="inline-flex items-center justify-center rounded-control p-xs text-text hover:bg-muted md:hidden"
                    aria-controls="main-menu" x-bind:aria-expanded="open.toString()" aria-expanded="false"
                    x-bind:aria-label="open ? @js(__('shared.close_menu')) : @js(__('shared.open_menu'))" aria-label="{{ __('shared.open_menu') }}"
                    x-on:click="open = ! open">
                    <x-ui.icon name="menu" x-show="! open" />
                    <x-ui.icon name="close" x-show="open" x-cloak />
                </button>
            </div>
            <div id="main-menu" class="hidden md:block" x-bind:class="{ 'hidden': ! open }" x-on:keydown.escape.window="open = false">
                @include('partials.navigation')
            </div>
        </nav>
        <div class="flex flex-1 flex-col">
            <header class="flex items-center justify-between gap-md border-b border-border bg-surface px-gutter py-sm">
                <h1 class="text-heading-2 font-semibold">{{ $heading ?? '' }}</h1>
                @auth
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-ui.button type="submit" variant="ghost">{{ __('identity.auth.logout') }}</x-ui.button>
                    </form>
                @endauth
            </header>
            <main id="main" class="mx-auto w-full max-w-page flex-1 px-gutter py-lg">
                @if (session('error'))
                    <x-ui.alert :tone="\App\Modules\Shared\Enums\Tone::Danger" class="mb-md">{{ session('error') }}</x-ui.alert>
                @endif
                @if (session('status'))
                    <x-ui.alert :tone="\App\Modules\Shared\Enums\Tone::Success" class="mb-md">{{ session('status') }}</x-ui.alert>
                @endif
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
