<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Actions;

use App\Modules\Customers\Models\Customer;
use App\Modules\Quotes\Models\Quote;

/**
 * Asigna el cliente a una cotización que se hizo a un prospecto (paso previo a crear el expediente).
 * Los datos del prospecto se conservan como historia; queda auditado por el modelo.
 */
final class AssignQuoteCustomerAction
{
    public function execute(Quote $quote, Customer $customer): Quote
    {
        $quote->customer_id = $customer->id;
        $quote->save();

        return $quote;
    }
}
