<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Events;

use App\Modules\Invoicing\Enums\InvoiceType;
use App\Modules\Shared\IntegrationEvents\IntegrationEvent;
use App\Modules\Shared\IntegrationEvents\PublishesToOutbox;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/** Se emitió una factura o nota (después de confirmar la transacción). */
final readonly class InvoiceIssued implements IntegrationEvent, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use PublishesToOutbox;

    public const NAME = 'invoicing.invoice_issued';

    public function __construct(
        public string $invoiceUlid,
        public InvoiceType $type,
        public string $bookingUlid,
    ) {}

    public static function eventName(): string
    {
        return self::NAME;
    }
}
