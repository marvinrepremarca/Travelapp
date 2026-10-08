<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Models;

use App\Modules\Customers\Models\Customer;
use App\Modules\Quotes\Database\Factories\QuoteFactory;
use App\Modules\Quotes\Enums\AcceptanceChannel;
use App\Modules\Quotes\Enums\QuoteStatus;
use App\Modules\Shared\Concerns\HasVisibilityScope;
use App\Modules\Shared\Enums\AuditLogName;
use App\Modules\Shared\Enums\SalesChannel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Cotización de un cliente con opciones (A, B, C). Cada envío congela una versión inmutable.
 *
 * @property int $id
 * @property string $ulid
 * @property string|null $number
 * @property int $customer_id
 * @property int $owner_id
 * @property int|null $branch_id
 * @property string $title
 * @property string $sale_currency
 * @property SalesChannel $sales_channel
 * @property QuoteStatus $status
 * @property int $current_version
 * @property CarbonImmutable|null $valid_until
 * @property int|null $accepted_option_id
 * @property int|null $accepted_version
 * @property CarbonImmutable|null $accepted_at
 * @property AcceptanceChannel|null $acceptance_channel
 * @property string|null $acceptance_note
 * @property-read Customer $customer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, QuoteOption> $options
 */
final class Quote extends Model
{
    /** @use HasFactory<QuoteFactory> */
    use HasFactory;
    use HasUlids;
    use HasVisibilityScope;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = ['customer_id', 'title', 'sale_currency', 'sales_channel'];

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

    /** @return HasMany<QuoteOption, $this> */
    public function options(): HasMany
    {
        return $this->hasMany(QuoteOption::class)->orderBy('label');
    }

    /** @return HasMany<QuoteVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(QuoteVersion::class)->orderByDesc('version');
    }

    /** Vencida aunque el proceso programado aún no la haya marcado. */
    public function isPastValidity(CarbonImmutable $now): bool
    {
        return $this->valid_until instanceof CarbonImmutable && $this->valid_until->lessThanOrEqualTo($now);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['number', 'title', 'sale_currency', 'status', 'current_version', 'valid_until', 'accepted_option_id', 'accepted_version', 'accepted_at', 'acceptance_channel', 'owner_id'])
            ->logOnlyDirty()
            ->useLogName(AuditLogName::Quotes->value);
    }

    protected static function newFactory(): QuoteFactory
    {
        return QuoteFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'sales_channel' => SalesChannel::class,
            'status' => QuoteStatus::class,
            'current_version' => 'integer',
            'valid_until' => 'immutable_datetime',
            'accepted_version' => 'integer',
            'accepted_at' => 'immutable_datetime',
            'acceptance_channel' => AcceptanceChannel::class,
        ];
    }
}
