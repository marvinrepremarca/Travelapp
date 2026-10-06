<?php

declare(strict_types=1);

namespace App\Modules\Finance\Data;

use App\Modules\Finance\Enums\ReconciliationTarget;
use Brick\Money\Money;
use Carbon\CarbonImmutable;

/** Movimiento del sistema que debería verse en el banco. El importe va con el signo del extracto. */
final readonly class ReconciliationEntry
{
    public function __construct(
        public ReconciliationTarget $target,
        public string $ulid,
        public CarbonImmutable $occurredOn,
        public Money $amount,
        public ?string $reference,
        public string $label,
    ) {}

    public function key(): string
    {
        return $this->target->value . ':' . $this->ulid;
    }
}
