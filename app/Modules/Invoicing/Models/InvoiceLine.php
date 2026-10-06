<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Models;

use App\Modules\Invoicing\Enums\InvoiceLineKind;
use App\Modules\Shared\Enums\ProductType;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Línea de una factura o nota. Inmutable como su documento.
 *
 * @property int $id
 * @property int $invoice_id
 * @property int $position
 * @property string|null $booking_item_ulid
 * @property string $description
 * @property ProductType|null $product_type
 * @property InvoiceLineKind $kind
 * @property int $amount_minor
 * @property int $tax_minor
 */
final class InvoiceLine extends Model
{
    protected $fillable = ['invoice_id', 'position', 'booking_item_ulid', 'description', 'product_type', 'kind', 'amount_minor', 'tax_minor'];

    protected static function booted(): void
    {
        self::updating(static fn(): never => throw new LogicException('Las líneas de una factura no se editan.'));
        self::deleting(static fn(): never => throw new LogicException('Las líneas de una factura no se borran.'));
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'product_type' => ProductType::class,
            'kind' => InvoiceLineKind::class,
            'amount_minor' => 'integer',
            'tax_minor' => 'integer',
        ];
    }
}
