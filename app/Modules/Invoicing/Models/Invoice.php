<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Models;

use App\Modules\Customers\Enums\DocumentType;
use App\Modules\Invoicing\Enums\EInvoiceStatus;
use App\Modules\Invoicing\Enums\InvoiceType;
use App\Modules\Shared\Concerns\HasVisibilityScope;
use App\Modules\Shared\Enums\AuditLogName;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Factura o nota emitida. Documento fiscal inmutable: solo cambia su estado ante la facturación electrónica;
 * los errores se corrigen con notas crédito o débito.
 *
 * @property int $id
 * @property string $ulid
 * @property InvoiceType $type
 * @property string $prefix
 * @property int $sequence
 * @property string $number
 * @property int|null $related_invoice_id
 * @property string $booking_ulid
 * @property string $booking_number
 * @property string|null $booking_invoice_key
 * @property int $customer_id
 * @property string $customer_name
 * @property DocumentType $customer_document_type
 * @property string $customer_document_number
 * @property string|null $customer_email
 * @property string|null $customer_city
 * @property int $owner_id
 * @property int|null $branch_id
 * @property string $currency
 * @property int $third_party_minor
 * @property int $own_income_minor
 * @property int $tax_minor
 * @property int $total_minor
 * @property string|null $reason
 * @property EInvoiceStatus $e_invoice_status
 * @property string|null $e_invoice_reference
 * @property string|null $e_invoice_message
 * @property int $issued_by
 * @property CarbonImmutable $issued_at
 * @property-read Collection<int, InvoiceLine> $lines
 * @property-read Invoice|null $related
 */
final class Invoice extends Model
{
    use HasUlids;
    use HasVisibilityScope;
    use LogsActivity;

    /** Lo único que cambia después de emitir. */
    private const MUTABLE = ['e_invoice_status', 'e_invoice_reference', 'e_invoice_message', 'updated_at'];

    protected $fillable = [];

    protected static function booted(): void
    {
        self::updating(static function (self $invoice): void {
            if (array_diff(array_keys($invoice->getDirty()), self::MUTABLE) !== []) {
                throw new LogicException('Una factura emitida no se edita: usa una nota crédito o débito.');
            }
        });
        self::deleting(static fn(): never => throw new LogicException('Las facturas no se borran.'));
    }

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /** @return HasMany<InvoiceLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class)->orderBy('position');
    }

    /** @return BelongsTo<Invoice, $this> */
    public function related(): BelongsTo
    {
        return $this->belongsTo(self::class, 'related_invoice_id');
    }

    public function money(int $minor): Money
    {
        return Money::ofMinor($minor, $this->currency);
    }

    public function total(): Money
    {
        return $this->money($this->total_minor);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['type', 'number', 'booking_number', 'total_minor', 'currency', 'e_invoice_status'])
            ->logOnlyDirty()
            ->useLogName(AuditLogName::Invoicing->value);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => InvoiceType::class,
            'sequence' => 'integer',
            'customer_document_type' => DocumentType::class,
            'customer_document_number' => 'encrypted',
            'third_party_minor' => 'integer',
            'own_income_minor' => 'integer',
            'tax_minor' => 'integer',
            'total_minor' => 'integer',
            'e_invoice_status' => EInvoiceStatus::class,
            'issued_at' => 'immutable_datetime',
        ];
    }
}
