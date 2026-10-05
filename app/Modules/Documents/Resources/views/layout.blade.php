{{-- Diseño base de todos los PDF: membrete de la agencia y pie con RNT. Ver resources/css/pdf.css. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $documentTitle }}</title>
    {{-- Hoja estática del repositorio; único uso de salida sin escapar fuera del Markdown sanitizado. --}}
    <style>{!! $pdfStyles !!}</style>
    @if ($letterhead->primaryColor)
        <style>h2 { color: {{ $letterhead->primaryColor }}; } .letterhead { border-bottom-color: {{ $letterhead->primaryColor }}; }</style>
    @endif
</head>
<body>
    <table class="letterhead">
        <tr>
            <td>
                @if ($letterhead->logoDataUri)
                    <img class="logo" src="{{ $letterhead->logoDataUri }}" alt="{{ $letterhead->tradeName }}">
                @else
                    <h1>{{ $letterhead->tradeName }}</h1>
                @endif
            </td>
            <td class="agency">
                <strong>{{ $letterhead->legalName ?? $letterhead->tradeName }}</strong><br>
                @if ($letterhead->nit) {{ __('documents.nit', ['nit' => $letterhead->nit]) }}<br> @endif
                @if ($letterhead->address) {{ $letterhead->address }}<br> @endif
                {{ collect([$letterhead->phone, $letterhead->email, $letterhead->website])->filter()->implode(' · ') }}
            </td>
        </tr>
    </table>

    <h1>{{ $documentTitle }}</h1>
    @yield('content')

    <div class="footer">
        {{ $letterhead->tradeName }}
        @if ($letterhead->rntNumber) · {{ __('documents.rnt', ['rnt' => $letterhead->rntNumber]) }} @endif
        · {{ __('documents.generated', ['date' => now($timezone)->locale(app()->getLocale())->isoFormat('lll')]) }}
    </div>
</body>
</html>
