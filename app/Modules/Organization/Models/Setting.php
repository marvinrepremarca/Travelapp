<?php

declare(strict_types=1);

namespace App\Modules\Organization\Models;

use App\Modules\Organization\Enums\SettingKey;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Valor editable que sobrescribe el default de config/travel.php.
 *
 * @property int $id
 * @property SettingKey $key
 * @property mixed $value
 */
final class Setting extends Model
{
    use LogsActivity;

    protected $fillable = ['key', 'value'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->useLogName('organization');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'key' => SettingKey::class,
            'value' => 'json',
        ];
    }
}
