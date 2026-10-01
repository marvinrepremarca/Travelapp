<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use App\Modules\Pricing\Database\Factories\MarkupRuleFactory;
use App\Modules\Pricing\Enums\MarkupKind;
use App\Modules\Shared\Enums\AuditLogName;
use App\Modules\Shared\Enums\ProductType;
use App\Modules\Shared\Enums\SalesChannel;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Regla de markup. Los criterios vacíos significan "cualquiera"; gana la regla más específica.
 *
 * @property int $id
 * @property string $ulid
 * @property string $name
 * @property ProductType|null $product_type
 * @property int|null $supplier_id
 * @property string|null $destination_country
 * @property SalesChannel|null $sales_channel
 * @property MarkupKind $kind
 * @property int|null $rate_basis_points
 * @property int|null $amount_minor
 * @property string|null $currency
 * @property int|null $min_margin_basis_points
 * @property int $priority
 * @property CarbonImmutable $valid_from
 * @property CarbonImmutable|null $valid_until
 * @property bool $is_active
 */
final class MarkupRule extends Model
{
    /** @use HasFactory<MarkupRuleFactory> */
    use HasFactory;
    use HasUlids;
    use LogsActivity;

    protected $fillable = [
        'name', 'product_type', 'supplier_id', 'destination_country', 'sales_channel', 'kind', 'rate_basis_points',
        'amount_minor', 'currency', 'min_margin_basis_points', 'priority', 'valid_from', 'valid_until', 'is_active',
    ];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function amount(): ?Money
    {
        return $this->amount_minor === null || $this->currency === null ? null : Money::ofMinor($this->amount_minor, $this->currency);
    }

    /** Número de criterios definidos: a más criterios, más específica. */
    public function specificity(): int
    {
        return count(array_filter([$this->product_type, $this->supplier_id, $this->destination_country, $this->sales_channel], static fn(mixed $value): bool => $value !== null));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->useLogName(AuditLogName::Pricing->value);
    }

    protected static function newFactory(): MarkupRuleFactory
    {
        return MarkupRuleFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'product_type' => ProductType::class,
            'sales_channel' => SalesChannel::class,
            'kind' => MarkupKind::class,
            'valid_from' => 'immutable_date',
            'valid_until' => 'immutable_date',
            'is_active' => 'boolean',
        ];
    }
}
