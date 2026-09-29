@props(['name', 'type' => 'text', 'hint' => false])
@php
$invalid = $errors->has($name);
$describedBy = collect([$hint ? "{$name}-hint" : null, $invalid ? "{$name}-error" : null])->filter()->implode(' ');
@endphp
<input
    type="{{ $type }}"
    name="{{ $name }}"
    id="{{ $attributes->get('id', $name) }}"
    @if ($invalid) aria-invalid="true" @endif
    @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
    {{ $attributes->except('id')->class([
        'w-full rounded-control border bg-surface px-md py-sm text-body text-text',
        'border-danger' => $invalid,
        'border-border' => ! $invalid,
    ]) }}
>
