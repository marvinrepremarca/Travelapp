<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Models;

use App\Modules\Quotes\Enums\QuoteItemKind;
use App\Modules\Shared\Enums\AuditLogName;
use App\Modules\Shared\Enums\ProductType;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Servicio cotizado. Neto, venta y margen los calcula el servidor; el desglose de Pricing queda congelado.
 *
 * @property int $id
 * @property string $ulid
 * @property int $option_id
 * @property QuoteItemKind $kind
 * @property ProductType $product_type
 * @property string $description
 * @property int|null $catalog_product_id
 * @property int|null $supplier_id
 * @property string|null $destination_country
 * @property CarbonImmutable $service_date
 * @property int $nights
 * @property list<int> $passenger_ages
 * @property int $net_amount_minor
 * @property string $net_currency
 * @property int $sale_amount_minor
 * @property int $margin_amount_minor
 * @property array<string, mixed> $price_breakdown
 * @property-read QuoteOption $option
 */
final class QuoteItem extends Model
{
    use HasUlids;
    use LogsActivity;

    protected $fillable = [
        'option_id', 'kind', 'product_type', 'description', 'catalog_product_id', 'supplier_id', 'destination_country',
        'service_date', 'nights', 'passenger_ages',
    ];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /** @return BelongsTo<QuoteOption, $this> */
    public function option(): BelongsTo
    {
        return $this->belongsTo(QuoteOption::class, 'option_id');
    }

    public function netAmount(): Money
    {
        return Money::ofMinor($this->net_amount_minor, $this->net_currency);
    }

    public function saleAmount(): Money
    {
        return Money::ofMinor($this->sale_amount_minor, $this->saleCurrency());
    }

    public function marginAmount(): Money
    {
        return Money::ofMinor($this->margin_amount_minor, $this->saleCurrency());
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['kind', 'product_type', 'description', 'service_date', 'nights', 'net_amount_minor', 'net_currency', 'sale_amount_minor', 'margin_amount_minor'])
            ->logOnlyDirty()
            ->useLogName(AuditLogName::Quotes->value);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'kind' => QuoteItemKind::class,
            'product_type' => ProductType::class,
            'service_date' => 'immutable_date',
            'nights' => 'integer',
            'passenger_ages' => 'array',
            'net_amount_minor' => 'integer',
            'sale_amount_minor' => 'integer',
            'margin_amount_minor' => 'integer',
            'price_breakdown' => 'array',
        ];
    }

    private function saleCurrency(): string
    {
        return $this->price_breakdown['currency'];
    }
}
