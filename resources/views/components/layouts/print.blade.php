<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-surface">
    <main class="p-xl">
        {{ $slot }}
    </main>
</body>
</html>
