{{-- País por nombre; guarda el código ISO 3166-1 alfa-2. --}}
@props(['name', 'placeholder' => null, 'hint' => false])
<x-ui.select :name="$name" :options="app(\App\Modules\Shared\Support\IsoCatalog::class)->countries()" :placeholder="$placeholder ?? __('shared.choose_country')" :hint="$hint" {{ $attributes }} />
