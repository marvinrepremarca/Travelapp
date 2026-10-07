{{--
    Gráfico de torta (dona) accesible en SVG, sin librerías. Colores desde la paleta de tokens --color-chart-*.
    - items: list<array{label: string, value: int, display: string, share: string}>  (value en unidades menores o conteo; share = % ya formateado)
    - caption: título para lectores de pantalla; los datos también van en la leyenda y en una tabla oculta.
    - empty: texto cuando no hay datos.
--}}
@props(['items' => [], 'caption' => '', 'empty' => ''])
@php
    $palette = ['text-chart-1', 'text-chart-2', 'text-chart-3', 'text-chart-4', 'text-chart-5', 'text-chart-6'];
    $items = array_values(array_filter($items, static fn (array $item): bool => $item['value'] > 0));
    $total = array_sum(array_column($items, 'value'));
    // Radio cuya circunferencia es 100: cada porcentaje es directamente la longitud del trazo.
    $radius = 100 / (2 * M_PI);
    $offset = 0.0;
    $percentScale = 100;
    $box = 42;
    $center = $box / 2;
    $ring = 8;
@endphp
<figure {{ $attributes->class(['flex flex-col items-center gap-md sm:flex-row sm:items-center']) }}>
    @if ($total === 0)
        <p class="text-text-subtle">{{ $empty }}</p>
    @else
        <svg viewBox="0 0 {{ $box }} {{ $box }}" class="size-pie shrink-0 -rotate-90" role="img" aria-label="{{ $caption }}">
            <title>{{ $caption }}</title>
            <circle cx="{{ $center }}" cy="{{ $center }}" r="{{ $radius }}" fill="none" stroke="currentColor" stroke-width="{{ $ring }}" class="text-muted" />
            @foreach ($items as $index => $item)
                @php($share = $percentScale * $item['value'] / $total)
                <circle cx="{{ $center }}" cy="{{ $center }}" r="{{ $radius }}" fill="none" stroke="currentColor" stroke-width="{{ $ring }}"
                    stroke-dasharray="{{ $share }} {{ $percentScale - $share }}" stroke-dashoffset="{{ -$offset }}" class="{{ $palette[$index % count($palette)] }}">
                    <title>{{ $item['label'] }}: {{ $item['display'] }}</title>
                </circle>
                @php($offset += $share)
            @endforeach
        </svg>
        <ul class="flex w-full flex-col gap-xs text-caption" aria-hidden="true">
            @foreach ($items as $index => $item)
                <li class="flex items-center justify-between gap-sm">
                    <span class="flex items-center gap-xs">
                        <svg viewBox="0 0 10 10" class="size-sm shrink-0 {{ $palette[$index % count($palette)] }}"><rect width="10" height="10" rx="2" fill="currentColor" /></svg>
                        {{ $item['label'] }}
                    </span>
                    <span class="font-medium">{{ $item['display'] }} <span class="text-text-subtle">· {{ __('reports.rate', ['rate' => $item['share']]) }}</span></span>
                </li>
            @endforeach
        </ul>
        <table class="sr-only">
            <caption>{{ $caption }}</caption>
            @foreach ($items as $item)
                <tr><th scope="row">{{ $item['label'] }}</th><td>{{ $item['display'] }}</td></tr>
            @endforeach
        </table>
    @endif
</figure>
