<?php

declare(strict_types=1);

namespace App\Modules\Suppliers\Models;

use App\Modules\Shared\Enums\AuditLogName;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $supplier_id
 * @property string $name
 * @property string|null $position
 * @property string|null $email
 * @property string|null $phone
 * @property bool $is_active
 */
final class SupplierContact extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'position', 'email', 'phone', 'is_active'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'position', 'email', 'phone', 'is_active'])
            ->logOnlyDirty()
            ->useLogName(AuditLogName::Suppliers->value);
    }
}
