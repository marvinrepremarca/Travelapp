{{--
    Gráfico de barras accesible en SVG (sin librerías). Los colores salen de los tokens (currentColor + clases).
    - items: list<array{label: string, value: int, display: string}>  (value en unidades menores o conteo)
    - caption: título para lectores de pantalla; los datos también van en una tabla oculta.
--}}
@props(['items' => [], 'caption' => ''])
@php
    $max = max(1, ...array_map(static fn (array $item): int => $item['value'], $items ?: [['value' => 0]]));
    $count = max(1, count($items));
    $height = 100;
    $gap = 0.2;
    $width = 100 / $count;
@endphp
<figure {{ $attributes->class(['flex flex-col gap-xs']) }}>
    <svg viewBox="0 0 100 {{ $height }}" preserveAspectRatio="none" class="h-chart w-full text-brand" role="img" aria-label="{{ $caption }}">
        <title>{{ $caption }}</title>
        @foreach ($items as $index => $item)
            @php($barHeight = $height * $item['value'] / $max)
            <rect x="{{ $index * $width + $width * $gap / 2 }}" y="{{ $height - $barHeight }}" width="{{ $width * (1 - $gap) }}" height="{{ $barHeight }}" fill="currentColor" rx="0.5">
                <title>{{ $item['label'] }}: {{ $item['display'] }}</title>
            </rect>
        @endforeach
    </svg>
    <figcaption class="flex justify-between text-caption text-text-subtle">
        <span>{{ $items[0]['label'] ?? '' }}</span>
        <span>{{ $items[count($items) - 1]['label'] ?? '' }}</span>
    </figcaption>
    <table class="sr-only">
        <caption>{{ $caption }}</caption>
        @foreach ($items as $item)
            <tr><th scope="row">{{ $item['label'] }}</th><td>{{ $item['display'] }}</td></tr>
        @endforeach
    </table>
</figure>
