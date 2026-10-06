<?php

declare(strict_types=1);

namespace App\Modules\Finance\Enums;

use App\Modules\Shared\Enums\Tone;

/** Estado de una línea del extracto bancario frente al sistema. */
enum StatementLineStatus: string
{
    case Pending = 'pending';
    case Matched = 'matched';
    /** Sin contrapartida en el sistema (comisiones, impuestos bancarios…), con nota obligatoria. */
    case Ignored = 'ignored';

    public function label(): string
    {
        return __("finance.statement_line_status.{$this->value}");
    }

    public function tone(): Tone
    {
        return match ($this) {
            self::Pending => Tone::Warning,
            self::Matched => Tone::Success,
            self::Ignored => Tone::Neutral,
        };
    }
}
