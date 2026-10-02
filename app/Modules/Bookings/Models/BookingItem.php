<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Models;

use App\Modules\Bookings\Enums\BookingItemStatus;
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
 * Servicio del expediente con precio congelado de la cotización y su estado con el proveedor.
 *
 * @property int $id
 * @property string $ulid
 * @property int $booking_id
 * @property string $kind
 * @property ProductType $product_type
 * @property string $description
 * @property string|null $catalog_product_ulid
 * @property string|null $catalog_departure_ulid
 * @property string|null $seat_hold_ulid
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
 * @property BookingItemStatus $status
 * @property string|null $supplier_confirmation
 * @property string|null $status_note
 * @property CarbonImmutable $status_changed_at
 * @property-read Booking $booking
 */
final class BookingItem extends Model
{
    use HasUlids;
    use LogsActivity;

    protected $fillable = [
        'booking_id', 'kind', 'product_type', 'description', 'catalog_product_ulid', 'supplier_id', 'destination_country',
        'service_date', 'nights', 'passenger_ages',
    ];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /** @return BelongsTo<Booking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function isOwnProduct(): bool
    {
        return $this->catalog_product_ulid !== null;
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
            ->logOnly(['status', 'supplier_confirmation', 'status_note', 'catalog_departure_ulid', 'seat_hold_ulid', 'sale_amount_minor', 'net_amount_minor'])
            ->logOnlyDirty()
            ->useLogName(AuditLogName::Bookings->value);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'product_type' => ProductType::class,
            'service_date' => 'immutable_date',
            'nights' => 'integer',
            'passenger_ages' => 'array',
            'net_amount_minor' => 'integer',
            'sale_amount_minor' => 'integer',
            'margin_amount_minor' => 'integer',
            'price_breakdown' => 'array',
            'status' => BookingItemStatus::class,
            'status_changed_at' => 'immutable_datetime',
        ];
    }

    private function saleCurrency(): string
    {
        return (string) $this->price_breakdown['currency'];
    }
}
