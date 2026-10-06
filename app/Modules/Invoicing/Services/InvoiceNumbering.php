<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Services;

use App\Modules\Invoicing\Enums\InvoiceType;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Consecutivo sin saltos por tipo de documento. Debe llamarse dentro de la transacción que guarda el documento:
 * el bloqueo de la fila se libera al confirmar, y si la emisión falla el número no se consume.
 */
final class InvoiceNumbering
{
    /** @return array{prefix: string, sequence: int, number: string} */
    public function next(InvoiceType $type): array
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('La numeración de facturas requiere una transacción.');
        }

        $row = DB::table('invoice_sequences')->where('type', $type->value)->lockForUpdate()->first(['id', 'next_number']);
        if ($row === null) {
            throw new LogicException("No existe el consecutivo para {$type->value}.");
        }

        $sequence = (int) $row->next_number;
        DB::table('invoice_sequences')->where('id', $row->id)->update(['next_number' => $sequence + 1, 'updated_at' => now()]);

        return ['prefix' => $type->prefix(), 'sequence' => $sequence, 'number' => $type->prefix() . $sequence];
    }
}
