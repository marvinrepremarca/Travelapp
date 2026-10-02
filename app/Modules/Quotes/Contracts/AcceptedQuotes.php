<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Contracts;

use App\Modules\Quotes\Data\AcceptedOption;
use App\Modules\Quotes\Exceptions\QuoteRuleViolation;

/** Lectura de cotizaciones aceptadas para convertirlas en expediente (Bookings). */
interface AcceptedQuotes
{
    /**
     * Opción aceptada con los ítems de la versión que el cliente aceptó.
     *
     * @throws QuoteRuleViolation si la cotización no está aceptada
     */
    public function acceptedOption(string $quoteUlid): AcceptedOption;
}
