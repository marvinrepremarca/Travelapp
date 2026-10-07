<?php

declare(strict_types=1);

namespace App\Modules\Operations\Models;

use App\Modules\Shared\Enums\AuditLogName;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Guía de turismo que acompaña las salidas.
 *
 * @property int $id
 * @property string $ulid
 * @property string $name
 * @property string $phone
 * @property string|null $languages
 * @property string|null $license_number
 * @property bool $is_active
 */
final class Guide extends Model
{
    use HasUlids;
    use LogsActivity;

    protected $fillable = ['name', 'phone', 'languages', 'license_number', 'is_active'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->useLogName(AuditLogName::Operations->value);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
