{{--
    Gráfico de barras accesible en SVG (sin librerías). Altura fija desde el token --spacing-chart; colores desde tokens.
    - items: list<array{label: string, value: int, display: string}>  (value en unidades menores o conteo)
    - caption: título para lectores de pantalla; los datos también van en una tabla oculta.
--}}
@props(['items' => [], 'caption' => ''])
@php
    $peak = array_reduce($items, static fn (?array $carry, array $item): ?array => $carry === null || $item['value'] > $carry['value'] ? $item : $carry);
    $max = max(1, $peak['value'] ?? 0);
    $count = max(1, count($items));
    $height = 100;
    $width = 100;
    $gap = 0.25;
    $slot = $width / $count;
    $gridLines = [0.25, 0.5, 0.75];
    $middle = intdiv(count($items), 2);
@endphp
<figure {{ $attributes->class(['flex flex-col gap-xs']) }}>
    <div class="flex items-baseline justify-between gap-sm text-caption text-text-subtle">
        <span>{{ $peak !== null && $peak['value'] > 0 ? __('reports.chart.peak', ['label' => $peak['label'], 'value' => $peak['display']]) : __('reports.chart.no_data') }}</span>
    </div>
    <div class="h-chart w-full overflow-hidden border-b border-border">
        <svg viewBox="0 0 {{ $width }} {{ $height }}" preserveAspectRatio="none" class="block h-full w-full" role="img" aria-label="{{ $caption }}">
            <title>{{ $caption }}</title>
            @foreach ($gridLines as $line)
                <line x1="0" x2="{{ $width }}" y1="{{ $height * $line }}" y2="{{ $height * $line }}" stroke="currentColor" vector-effect="non-scaling-stroke" class="text-border" />
            @endforeach
            @foreach ($items as $index => $item)
                @php($barHeight = $height * $item['value'] / $max)
                <rect x="{{ $index * $slot + $slot * $gap / 2 }}" y="{{ $height - $barHeight }}" width="{{ $slot * (1 - $gap) }}" height="{{ $barHeight }}" fill="currentColor"
                    @class(['text-brand' => $item['value'] < $max, 'text-accent' => $item['value'] === $max && $item['value'] > 0])>
                    <title>{{ $item['label'] }}: {{ $item['display'] }}</title>
                </rect>
            @endforeach
        </svg>
    </div>
    <figcaption class="flex justify-between text-caption text-text-subtle" aria-hidden="true">
        <span>{{ $items[0]['label'] ?? '' }}</span>
        <span>{{ $items[$middle]['label'] ?? '' }}</span>
        <span>{{ $items[count($items) - 1]['label'] ?? '' }}</span>
    </figcaption>
    <table class="sr-only">
        <caption>{{ $caption }}</caption>
        @foreach ($items as $item)
            <tr><th scope="row">{{ $item['label'] }}</th><td>{{ $item['display'] }}</td></tr>
        @endforeach
    </table>
</figure>
