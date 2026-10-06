<?php

declare(strict_types=1);

namespace App\Modules\Search\Data;

/** Opción del autocompletar: lo que se guarda (código) y lo que ve el usuario (nombre natural). */
final readonly class PlaceSuggestion
{
    public function __construct(
        public string $value,
        public string $label,
        public string $detail,
    ) {}
}
