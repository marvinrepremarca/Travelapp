<?php

declare(strict_types=1);

namespace App\Modules\Suppliers\Models;

use App\Modules\Shared\Enums\AuditLogName;
use App\Modules\Suppliers\Database\Factories\SupplierFactory;
use App\Modules\Suppliers\Enums\PaymentTerms;
use App\Modules\Suppliers\Enums\SupplierStanding;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Proveedor de servicios turísticos. Es un catálogo de la agencia (sin alcance por sucursal).
 *
 * @property int $id
 * @property string $ulid
 * @property string $legal_name
 * @property string $trade_name
 * @property string $tax_id
 * @property string $country
 * @property bool $is_tourism_provider
 * @property string|null $rnt_number
 * @property CarbonImmutable|null $rnt_expires_on
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $website
 * @property PaymentTerms $payment_terms
 * @property int $payment_days
 * @property string $payment_currency
 * @property string|null $notes
 * @property bool $is_active
 */
final class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use HasFactory;
    use HasUlids;
    use LogsActivity;
    use SoftDeletes;

    /** País del que se exige RNT a los prestadores turísticos. */
    public const RNT_COUNTRY = 'CO';

    protected $fillable = [
        'legal_name', 'trade_name', 'tax_id', 'country', 'is_tourism_provider', 'rnt_number', 'rnt_expires_on',
        'email', 'phone', 'website', 'payment_terms', 'payment_days', 'payment_currency', 'notes',
    ];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function requiresRnt(): bool
    {
        return $this->is_tourism_provider && $this->country === self::RNT_COUNTRY;
    }

    /** Situación para nuevas reservas en una fecha dada. */
    public function standingOn(CarbonImmutable $date, int $warningDays): SupplierStanding
    {
        return match (true) {
            ! $this->is_active => SupplierStanding::Inactive,
            ! $this->requiresRnt() => SupplierStanding::Bookable,
            $this->rnt_number === null || $this->rnt_expires_on === null => SupplierStanding::RntMissing,
            $this->rnt_expires_on->lessThan($date->startOfDay()) => SupplierStanding::RntExpired,
            $this->rnt_expires_on->lessThanOrEqualTo($date->addDays($warningDays)) => SupplierStanding::RntExpiring,
            default => SupplierStanding::Bookable,
        };
    }

    /** @return HasMany<SupplierContact, $this> */
    public function contacts(): HasMany
    {
        return $this->hasMany(SupplierContact::class)->orderBy('name');
    }

    /** @return HasMany<SupplierBankAccount, $this> */
    public function bankAccounts(): HasMany
    {
        return $this->hasMany(SupplierBankAccount::class);
    }

    /** @return HasMany<SupplierCommission, $this> */
    public function commissions(): HasMany
    {
        return $this->hasMany(SupplierCommission::class)->orderBy('product_type')->orderByDesc('valid_from');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([...$this->fillable, 'is_active'])
            ->logOnlyDirty()
            ->useLogName(AuditLogName::Suppliers->value);
    }

    protected static function newFactory(): SupplierFactory
    {
        return SupplierFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_tourism_provider' => 'boolean',
            'is_active' => 'boolean',
            'rnt_expires_on' => 'immutable_date',
            'payment_terms' => PaymentTerms::class,
            'payment_days' => 'integer',
        ];
    }
}
