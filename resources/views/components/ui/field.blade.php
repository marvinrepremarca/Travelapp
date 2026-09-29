@props(['label', 'for', 'error' => null, 'hint' => null])
@php
$message = $error ?? $errors->first($for);
@endphp
<div {{ $attributes->class(['flex flex-col gap-xs']) }}>
    <label for="{{ $for }}" class="text-body font-medium text-text">{{ $label }}</label>
    {{ $slot }}
    @if ($hint)
        <p id="{{ $for }}-hint" class="text-caption text-text-subtle">{{ $hint }}</p>
    @endif
    @if ($message)
        <p id="{{ $for }}-error" class="text-caption text-danger" role="alert">{{ $message }}</p>
    @endif
</div>
