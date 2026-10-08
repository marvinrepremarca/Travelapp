<?php

declare(strict_types=1);

namespace App\Modules\Shared\IntegrationEvents;

/**
 * Evento que una capacidad publica para que otras lo reconozcan (ADR-0007). Se guarda en la bitácora con
 * sus propiedades públicas del constructor: solo escalares, enums respaldados y CarbonImmutable.
 */
interface IntegrationEvent
{
    /** Nombre estable en la bitácora: no cambia aunque la clase se mueva o se renombre. */
    public static function eventName(): string;
}
