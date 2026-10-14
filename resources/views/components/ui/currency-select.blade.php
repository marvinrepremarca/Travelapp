{{-- Moneda por nombre; guarda el código ISO 4217. --}}
@props(['name', 'placeholder' => null, 'hint' => false])
<x-ui.select :name="$name" :options="app(\App\Modules\Shared\Support\IsoCatalog::class)->currencies()" :placeholder="$placeholder ?? __('shared.choose_currency')" :hint="$hint" {{ $attributes }} />
