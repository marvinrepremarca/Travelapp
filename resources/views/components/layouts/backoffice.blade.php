<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body>
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:p-sm">{{ __('shared.skip_to_content') }}</a>
    <div class="flex min-h-screen flex-col md:flex-row">
        <nav aria-label="{{ __('shared.main_navigation') }}" class="border-b border-border bg-surface p-md md:w-sidebar md:border-b-0 md:border-r">
            <p class="text-heading-3 font-semibold text-brand">{{ $agencyName ?? config('app.name') }}</p>
            @include('partials.navigation')
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
