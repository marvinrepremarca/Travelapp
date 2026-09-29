<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body>
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:p-sm">{{ __('shared.skip_to_content') }}</a>
    <header class="border-b border-border bg-surface px-gutter py-md">
        <p class="text-heading-3 font-semibold text-brand">{{ config('app.name') }}</p>
    </header>
    <main id="main" class="mx-auto w-full max-w-page px-gutter py-section">
        {{ $slot }}
    </main>
</body>
</html>
