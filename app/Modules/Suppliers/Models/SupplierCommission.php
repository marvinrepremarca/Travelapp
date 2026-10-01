<?php

declare(strict_types=1);

namespace App\Modules\Suppliers\Models;

use App\Modules\Shared\Enums\AuditLogName;
use App\Modules\Shared\Enums\ProductType;
use App\Modules\Shared\ValueObjects\Percentage;
use App\Modules\Suppliers\Enums\CommissionBase;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Comisión pactada con el proveedor para un tipo de producto y una vigencia.
 *
 * @property int $id
 * @property int $supplier_id
 * @property ProductType $product_type
 * @property int $rate_basis_points
 * @property CommissionBase $base
 * @property CarbonImmutable $valid_from
 * @property CarbonImmutable|null $valid_until
 * @property int $created_by
 */
final class SupplierCommission extends Model
{
    use LogsActivity;

    protected $fillable = ['product_type', 'rate_basis_points', 'base', 'valid_from', 'valid_until', 'created_by'];

    public function rate(): Percentage
    {
        return Percentage::fromBasisPoints($this->rate_basis_points);
    }

    public function appliesOn(CarbonImmutable $date): bool
    {
        $day = $date->startOfDay();

        return $this->valid_from->lessThanOrEqualTo($day)
            && ($this->valid_until === null || $this->valid_until->greaterThanOrEqualTo($day));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->useLogName(AuditLogName::Suppliers->value);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'product_type' => ProductType::class,
            'base' => CommissionBase::class,
            'rate_basis_points' => 'integer',
            'valid_from' => 'immutable_date',
            'valid_until' => 'immutable_date',
        ];
    }
}
