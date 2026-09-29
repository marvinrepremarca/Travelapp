<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body>
    <main class="flex min-h-screen items-center justify-center px-gutter py-section">
        <div class="w-full max-w-form">
            <p class="mb-lg text-center text-heading-2 font-semibold text-brand">{{ config('app.name') }}</p>
            @if (session('status'))
                <x-ui.alert :tone="\App\Modules\Shared\Enums\Tone::Success" class="mb-md">{{ session('status') }}</x-ui.alert>
            @endif
            <x-ui.card :title="$heading ?? null">
                {{ $slot }}
            </x-ui.card>
        </div>
    </main>
</body>
</html>
