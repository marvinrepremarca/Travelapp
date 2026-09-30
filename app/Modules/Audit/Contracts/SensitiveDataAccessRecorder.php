<?php

declare(strict_types=1);

namespace App\Modules\Audit\Contracts;

use App\Modules\Audit\Enums\SensitiveDataAccessType;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Registra quién vio o exportó un dato personal sensible (pasaporte, documento,
 * fecha de nacimiento, salud, menores). Ley 1581 de 2012 / GDPR.
 */
interface SensitiveDataAccessRecorder
{
    public function record(Authenticatable $viewer, Model $subject, string $field, SensitiveDataAccessType $type, ?string $reason = null): void;
}
