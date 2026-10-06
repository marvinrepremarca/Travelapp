<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Adapters\Null;

use App\Modules\Invoicing\Contracts\EInvoicingProvider;
use App\Modules\Invoicing\Data\EInvoiceDocument;
use App\Modules\Invoicing\Data\EInvoiceResult;
use App\Modules\Invoicing\Enums\EInvoiceStatus;

/** Sin facturación electrónica: los documentos quedan como facturas internas. */
final class NullEInvoicingProvider implements EInvoicingProvider
{
    public const KEY = 'internal';

    public function key(): string
    {
        return self::KEY;
    }

    public function submit(EInvoiceDocument $document): EInvoiceResult
    {
        return new EInvoiceResult(EInvoiceStatus::NotApplicable);
    }
}
