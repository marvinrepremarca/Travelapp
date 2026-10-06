<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Enums;

/** Documento de venta. Cada tipo lleva su propio consecutivo y prefijo ⚙. */
enum InvoiceType: string
{
    case Invoice = 'invoice';
    case CreditNote = 'credit_note';
    case DebitNote = 'debit_note';

    public function label(): string
    {
        return __("invoicing.type.{$this->value}");
    }

    public function prefix(): string
    {
        return config()->string("travel.invoicing.prefixes.{$this->value}");
    }
}
