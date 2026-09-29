<?php

declare(strict_types=1);

namespace App\Modules\Organization\Models;

use App\Modules\Organization\Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Sucursal o punto de venta de la agencia.
 *
 * @property int $id
 * @property string $ulid
 * @property string $code
 * @property string $name
 * @property string $timezone
 * @property bool $is_active
 */
final class Branch extends Model
{
    /** @use HasFactory<BranchFactory> */
    use HasFactory;
    use HasUlids;

    protected $fillable = ['code', 'name', 'timezone', 'is_active'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
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
