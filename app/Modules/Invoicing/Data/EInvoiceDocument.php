<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Data;

use App\Modules\Invoicing\Enums\InvoiceType;
use Brick\Money\Money;
use Carbon\CarbonImmutable;

/** Documento que se envía al proveedor de facturación electrónica (sin modelos de Eloquent). */
final readonly class EInvoiceDocument
{
    /** @param  list<array{description: string, kind: string, amount: Money, tax: Money}>  $lines */
    public function __construct(
        public InvoiceType $type,
        public string $number,
        public ?string $relatedNumber,
        public CarbonImmutable $issuedAt,
        public string $customerName,
        public string $customerDocumentType,
        public string $customerDocumentNumber,
        public ?string $customerEmail,
        public Money $total,
        public Money $tax,
        public array $lines,
    ) {}
}
