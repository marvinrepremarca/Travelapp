<?php

declare(strict_types=1);

namespace App\Modules\Shared\Exceptions;

use RuntimeException;

/**
 * Violación de una regla de negocio. El handler la convierte en 422 (API) o mensaje flash (web).
 * Las subclases exponen constructores nombrados y un código estable para la API.
 */
abstract class BusinessRuleException extends RuntimeException
{
    /** Código estable y legible por máquina (snake_case), usado por la API. */
    abstract public function errorCode(): string;
}
