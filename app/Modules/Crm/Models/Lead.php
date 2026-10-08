<?php

declare(strict_types=1);

namespace App\Modules\Crm\Models;

use App\Modules\Crm\Database\Factories\LeadFactory;
use App\Modules\Crm\Enums\LeadStatus;
use App\Modules\Crm\Enums\LostReason;
use App\Modules\Customers\Models\Customer;
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
 * Prospecto: solicitud aún no convertida en venta.
 *
 * @property int $id
 * @property string $ulid
 * @property string $contact_name
 * @property string|null $email
 * @property string|null $phone
 * @property SalesChannel $channel
 * @property string|null $destination
 * @property CarbonImmutable|null $travel_start
 * @property CarbonImmutable|null $travel_end
 * @property int|null $travelers_count
 * @property string|null $notes
 * @property LeadStatus $status
 * @property LostReason|null $lost_reason
 * @property string|null $lost_note
 * @property int|null $customer_id
 * @property int $owner_id
 * @property int|null $branch_id
 * @property CarbonImmutable|null $status_changed_at
 */
final class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory;
    use HasUlids;
    use HasVisibilityScope;
    use LogsActivity;
    use SoftDeletes;

    /** Estado, motivo de pérdida, cliente, responsable y sucursal se asignan solo en las Actions. */
    protected $fillable = ['contact_name', 'email', 'phone', 'channel', 'destination', 'travel_start', 'travel_end', 'travelers_count', 'notes'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /** Título sugerido para la cotización que nace de este lead. */
    public function quoteTitle(): string
    {
        return $this->destination === null || $this->destination === '' ? __('crm.leads.quote_title_generic', ['name' => $this->contact_name]) : __('crm.leads.quote_title', ['destination' => $this->destination]);
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

    /** @return HasMany<LeadInteraction, $this> */
    public function interactions(): HasMany
    {
        return $this->hasMany(LeadInteraction::class)->latest('occurred_at')->latest('id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['contact_name', 'status', 'lost_reason', 'customer_id', 'owner_id'])
            ->logOnlyDirty()
            ->useLogName(AuditLogName::Crm->value);
    }

    protected static function newFactory(): LeadFactory
    {
        return LeadFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'channel' => SalesChannel::class,
            'status' => LeadStatus::class,
            'lost_reason' => LostReason::class,
            'travel_start' => 'immutable_date',
            'travel_end' => 'immutable_date',
            'travelers_count' => 'integer',
            'status_changed_at' => 'immutable_datetime',
        ];
    }
}
