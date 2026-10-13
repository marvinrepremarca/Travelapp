<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services;

use Illuminate\Support\Str;

/**
 * Genera contraseñas temporales que cumplen la política de la app (mayúsculas, minúsculas y números).
 * La contraseña nunca se guarda en texto plano: solo se muestra una vez al administrador.
 */
final class TemporaryPasswordGenerator
{
    private const LENGTH = 16;

    public function generate(): string
    {
        do {
            $password = Str::password(self::LENGTH, symbols: false);
        } while (! $this->meetsPolicy($password));

        return $password;
    }

    private function meetsPolicy(string $password): bool
    {
        return preg_match('/[a-z]/', $password) === 1
            && preg_match('/[A-Z]/', $password) === 1
            && preg_match('/[0-9]/', $password) === 1;
    }
}
