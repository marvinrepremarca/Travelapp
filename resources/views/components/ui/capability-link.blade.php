{{-- Enlace a una pantalla de otra capacidad: si esa capacidad está apagada se muestra solo el texto, con el mismo diseño pero sin estilo de enlace (ADR-0007). --}}
@props(['route', 'params' => []])
@php($url = \App\Modules\Shared\Capabilities\Capabilities::routeUrl($route, $params))
@if ($url !== null)
    <a href="{{ $url }}" wire:navigate {{ $attributes }}>{{ $slot }}</a>
@else
    <span {{ $attributes->except('class') }} @class(array_diff(explode(' ', (string) $attributes->get('class')), ['text-brand', 'underline', 'hover:bg-muted']))>{{ $slot }}</span>
@endif
