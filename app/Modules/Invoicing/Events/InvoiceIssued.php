<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Events;

use App\Modules\Invoicing\Enums\InvoiceType;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/** Se emitió una factura o nota (después de confirmar la transacción). */
final readonly class InvoiceIssued implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public string $invoiceUlid,
        public InvoiceType $type,
        public string $bookingUlid,
    ) {}
}
