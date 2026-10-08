<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Models;

use App\Modules\Bookings\Enums\BookingStatus;
use App\Modules\Customers\Models\Customer;
use App\Modules\Shared\Concerns\HasVisibilityScope;
use App\Modules\Shared\Enums\AuditLogName;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Expediente: el viaje vendido a un cliente, con sus servicios. Su estado se deriva de los ítems.
 *
 * @property int $id
 * @property string $ulid
 * @property string|null $number
 * @property int $customer_id
 * @property int $owner_id
 * @property int|null $branch_id
 * @property string|null $quote_ulid
 * @property string|null $quote_number
 * @property int|null $quote_version
 * @property string $title
 * @property string $sale_currency
 * @property BookingStatus $status
 * @property-read Customer $customer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, BookingItem> $items
 */
final class Booking extends Model
{
    use HasUlids;
    use HasVisibilityScope;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = ['customer_id', 'title', 'sale_currency', 'quote_ulid', 'quote_number', 'quote_version'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return HasMany<BookingItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class)->orderBy('service_date')->orderBy('id');
    }

    /** Total de venta de los servicios vigentes (sin rechazados ni cancelados). */
    public function saleTotal(): Money
    {
        return $this->items
            ->reject(static fn(BookingItem $item): bool => $item->status->isClosed())
            ->reduce(static fn(Money $carry, BookingItem $item): Money => $carry->plus($item->saleAmount()), Money::zero($this->sale_currency));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['number', 'title', 'status', 'owner_id', 'quote_ulid'])
            ->logOnlyDirty()
            ->useLogName(AuditLogName::Bookings->value);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['status' => BookingStatus::class, 'quote_version' => 'integer'];
    }
}
