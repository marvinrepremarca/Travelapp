<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Enums;

use App\Modules\Shared\Enums\Tone;

/** Estado del documento ante el proveedor de facturación electrónica (puerto EInvoicingProvider). */
enum EInvoiceStatus: string
{
    /** Sin facturación electrónica activa (proveedor Null). */
    case NotApplicable = 'not_applicable';
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';

    public function label(): string
    {
        return __("invoicing.e_invoice_status.{$this->value}");
    }

    public function tone(): Tone
    {
        return match ($this) {
            self::NotApplicable => Tone::Neutral,
            self::Pending => Tone::Warning,
            self::Accepted => Tone::Success,
            self::Rejected => Tone::Danger,
        };
    }
}
