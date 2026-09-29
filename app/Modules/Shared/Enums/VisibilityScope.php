<?php

declare(strict_types=1);

namespace App\Modules\Shared\Enums;

/** Alcance de visibilidad de datos operativos de un usuario (ADR-0002). */
enum VisibilityScope: string
{
    case Own = 'own';
    case Branch = 'branch';
    case All = 'all';

    public function label(): string
    {
        return __("shared.visibility_scope.{$this->value}");
    }
}
