<?php

declare(strict_types=1);

namespace App\Modules\Finance\Data;

/** Datos validados del formulario de cuenta bancaria. */
final readonly class BankAccountData
{
    public function __construct(
        public string $name,
        public string $bankName,
        public string $accountLastDigits,
        public string $currency,
        public bool $isActive,
        public StatementFormat $format,
    ) {}
}
