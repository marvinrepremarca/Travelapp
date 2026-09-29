@props(['name', 'options' => [], 'placeholder' => null, 'hint' => false])
@php
$invalid = $errors->has($name);
$describedBy = collect([$hint ? "{$name}-hint" : null, $invalid ? "{$name}-error" : null])->filter()->implode(' ');
@endphp
<select
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
    @if ($placeholder !== null)
        <option value="">{{ $placeholder }}</option>
    @endif
    @foreach ($options as $value => $label)
        <option value="{{ $value }}" wire:key="{{ $name }}-option-{{ $value }}">{{ $label }}</option>
    @endforeach
</select>
