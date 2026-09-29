<?php

declare(strict_types=1);

namespace App\Modules\Shared\Enums;

/** Tono visual semántico que los enums de dominio exponen para que la vista no decida colores. */
enum Tone: string
{
    case Neutral = 'neutral';
    case Info = 'info';
    case Success = 'success';
    case Warning = 'warning';
    case Danger = 'danger';
}
