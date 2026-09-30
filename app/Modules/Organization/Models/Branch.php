<?php

declare(strict_types=1);

namespace App\Modules\Organization\Models;

use App\Modules\Organization\Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Sucursal o punto de venta de la agencia. Nunca se borra: se desactiva.
 *
 * @property int $id
 * @property string $ulid
 * @property string $code
 * @property string $name
 * @property string|null $city
 * @property string|null $address
 * @property string|null $phone
 * @property string|null $email
 * @property string $timezone
 * @property int|null $manager_id
 * @property bool $is_active
 */
final class Branch extends Model
{
    /** @use HasFactory<BranchFactory> */
    use HasFactory;
    use HasUlids;
    use LogsActivity;

    /** `manager_id` e `is_active` se asignan solo desde las Actions. */
    protected $fillable = ['code', 'name', 'city', 'address', 'phone', 'email', 'timezone'];

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
        return LogOptions::defaults()
            ->logOnly([...$this->fillable, 'manager_id', 'is_active'])
            ->logOnlyDirty()
            ->useLogName('organization');
    }

    /** @param Builder<self> $query */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    protected static function newFactory(): BranchFactory
    {
        return BranchFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
