@props(['tone' => \App\Modules\Shared\Enums\Tone::Info])
@php
$classes = match ($tone) {
    \App\Modules\Shared\Enums\Tone::Success => 'border-success text-success',
    \App\Modules\Shared\Enums\Tone::Warning => 'border-warning text-warning',
    \App\Modules\Shared\Enums\Tone::Danger => 'border-danger text-danger',
    default => 'border-info text-info',
};
@endphp
<div role="status" {{ $attributes->class(['rounded-control border bg-surface px-md py-sm text-body', $classes]) }}>{{ $slot }}</div>
