<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Contracts;

use App\Modules\Invoicing\Data\EInvoiceDocument;
use App\Modules\Invoicing\Data\EInvoiceResult;

/**
 * Puerto de facturación electrónica (DIAN vía un proveedor tecnológico). Los adaptadores viven en Integrations
 * y se registran con el tag; el activo se elige en config travel.invoicing.e_invoicing_provider.
 * Se llama siempre en cola, fuera de la transacción que emite el documento.
 */
interface EInvoicingProvider
{
    public const TAG = 'invoicing.e_invoicing_providers';

    public function key(): string;

    /** Debe ser idempotente por número de documento. */
    public function submit(EInvoiceDocument $document): EInvoiceResult;
}
