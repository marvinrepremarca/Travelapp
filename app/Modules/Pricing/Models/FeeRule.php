<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use App\Modules\Pricing\Enums\FeeBasis;
use App\Modules\Shared\Enums\AuditLogName;
use App\Modules\Shared\Enums\ProductType;
use App\Modules\Shared\Enums\SalesChannel;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Fee de servicio (cargo por gestión) que se suma al precio y se muestra en el desglose.
 *
 * @property int $id
 * @property string $ulid
 * @property string $name
 * @property ProductType|null $product_type
 * @property SalesChannel|null $sales_channel
 * @property FeeBasis $basis
 * @property int $amount_minor
 * @property string $currency
 * @property CarbonImmutable $valid_from
 * @property CarbonImmutable|null $valid_until
 * @property bool $is_active
 */
final class FeeRule extends Model
{
    use HasUlids;
    use LogsActivity;

    protected $fillable = ['name', 'product_type', 'sales_channel', 'basis', 'amount_minor', 'currency', 'valid_from', 'valid_until', 'is_active'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function amount(): Money
    {
        return Money::ofMinor($this->amount_minor, $this->currency);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->useLogName(AuditLogName::Pricing->value);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'product_type' => ProductType::class,
            'sales_channel' => SalesChannel::class,
            'basis' => FeeBasis::class,
            'valid_from' => 'immutable_date',
            'valid_until' => 'immutable_date',
            'is_active' => 'boolean',
        ];
    }
}
