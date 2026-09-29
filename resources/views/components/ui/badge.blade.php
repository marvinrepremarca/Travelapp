@props(['tone' => \App\Modules\Shared\Enums\Tone::Neutral])
@php
$classes = match ($tone) {
    \App\Modules\Shared\Enums\Tone::Success => 'bg-success/10 text-success',
    \App\Modules\Shared\Enums\Tone::Warning => 'bg-warning/10 text-warning',
    \App\Modules\Shared\Enums\Tone::Danger => 'bg-danger/10 text-danger',
    \App\Modules\Shared\Enums\Tone::Info => 'bg-info/10 text-info',
    default => 'bg-muted text-text',
};
@endphp
<span {{ $attributes->class(['inline-flex items-center gap-xs rounded-pill px-sm py-xs text-caption font-medium', $classes]) }}>{{ $slot }}</span>
