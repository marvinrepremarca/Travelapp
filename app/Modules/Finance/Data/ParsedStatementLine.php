<?php

declare(strict_types=1);

namespace App\Modules\Finance\Data;

use Brick\Money\Money;
use Carbon\CarbonImmutable;

/** Fila leída del CSV del banco, antes de guardarse. */
final readonly class ParsedStatementLine
{
    public const DESCRIPTION_LENGTH = 255;

    public const REFERENCE_LENGTH = 100;

    public function __construct(
        public CarbonImmutable $postedOn,
        public string $description,
        public ?string $reference,
        public Money $amount,
    ) {}

    /** Huella sin el orden de aparición; el importador agrega la ocurrencia para filas idénticas. */
    public function fingerprint(): string
    {
        return implode('|', [$this->postedOn->toDateString(), $this->amount->getMinorAmount(), $this->reference ?? '', $this->description]);
    }
}
