<?php

declare(strict_types=1);

namespace App\Modules\Audit\Models;

use App\Modules\Audit\Enums\SensitiveDataAccessType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Registro inmutable de un acceso a un dato sensible. Nunca guarda el valor del dato.
 *
 * @property int $id
 * @property int $user_id
 * @property string $subject_type
 * @property int|string $subject_id
 * @property string $field
 * @property SensitiveDataAccessType $type
 * @property string|null $reason
 * @property string|null $ip_address
 * @property CarbonImmutable $accessed_at
 */
final class SensitiveDataAccess extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'subject_type', 'subject_id', 'field', 'type', 'reason', 'ip_address', 'accessed_at'];

    protected static function booted(): void
    {
        // Bitácora de solo inserción: modificar o borrar un registro rompería la trazabilidad.
        self::updating(static fn(): never => throw new LogicException(__('audit.errors.immutable')));
        self::deleting(static fn(): never => throw new LogicException(__('audit.errors.immutable')));
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => SensitiveDataAccessType::class,
            'accessed_at' => 'immutable_datetime',
        ];
    }
}
