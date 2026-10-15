<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('renders the copy button with compiled values for Alpine', function (): void {
    $html = Blade::render('<x-ui.copy-button text="clave\'secreta" label="Copiar contraseña" />');

    expect($html)->not->toContain('@js(')
        ->and($html)->toContain("text: 'clave\u0027secreta'")
        ->and($html)->toContain('x-on:click="copy()"')
        ->and($html)->toContain('aria-live="polite"');
});
