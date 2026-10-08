<?php

declare(strict_types=1);

namespace App\Modules\Shared\Capabilities;

use LogicException;

/** Error de despliegue: una capacidad encendida necesita otra que está apagada. Falla cerrado. */
final class CapabilityConfigurationInvalid extends LogicException
{
    /** @param list<string> $problems */
    public static function because(array $problems): self
    {
        return new self(__('capabilities.invalid_configuration', ['problems' => implode('; ', $problems)]));
    }
}
