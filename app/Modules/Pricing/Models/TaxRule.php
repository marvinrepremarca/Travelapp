<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use App\Modules\Shared\Enums\AuditLogName;
use App\Modules\Shared\Enums\ProductType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Impuesto sobre el ingreso de la agencia (markup + fee), con vigencia y tipos de producto exentos.
 * Los porcentajes cambian por ley: nunca son constantes en el código.
 *
 * @property int $id
 * @property string $ulid
 * @property string $name
 * @property int $rate_basis_points
 * @property list<string>|null $exempt_product_types
 * @property CarbonImmutable $valid_from
 * @property CarbonImmutable|null $valid_until
 * @property bool $is_active
 */
final class TaxRule extends Model
{
    use HasUlids;
    use LogsActivity;

    protected $fillable = ['name', 'rate_basis_points', 'exempt_product_types', 'valid_from', 'valid_until', 'is_active'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function exempts(ProductType $productType): bool
    {
        return in_array($productType->value, $this->exempt_product_types ?? [], true);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->useLogName(AuditLogName::Pricing->value);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'exempt_product_types' => 'array',
            'valid_from' => 'immutable_date',
            'valid_until' => 'immutable_date',
            'is_active' => 'boolean',
        ];
    }
}
