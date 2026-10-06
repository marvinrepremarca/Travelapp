<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Models;

use App\Modules\Invoicing\Enums\AdjustmentStatus;
use App\Modules\Shared\Enums\AuditLogName;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Solicitud de nota crédito sobre una factura, pendiente de aprobación de finanzas.
 *
 * @property int $id
 * @property string $ulid
 * @property int $invoice_id
 * @property AdjustmentStatus $status
 * @property string $reason
 * @property list<array{invoice_line_id: int, amount_minor: int, tax_minor: int}> $lines
 * @property int $total_minor
 * @property int $requested_by
 * @property string|null $approval_ulid
 * @property int|null $note_invoice_id
 * @property CarbonImmutable|null $decided_at
 * @property-read Invoice $invoice
 * @property-read Invoice|null $note
 */
final class InvoiceAdjustment extends Model
{
    use HasUlids;
    use LogsActivity;

    protected $fillable = ['invoice_id', 'status', 'reason', 'lines', 'total_minor', 'requested_by'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** @return BelongsTo<Invoice, $this> */
    public function note(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'note_invoice_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['status', 'total_minor', 'reason', 'note_invoice_id'])->logOnlyDirty()->useLogName(AuditLogName::Invoicing->value);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['status' => AdjustmentStatus::class, 'lines' => 'array', 'total_minor' => 'integer', 'decided_at' => 'immutable_datetime'];
    }
}
