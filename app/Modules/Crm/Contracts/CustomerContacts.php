<?php

declare(strict_types=1);

namespace App\Modules\Crm\Contracts;

use App\Modules\Crm\Data\CustomerWhatsApp;

/** Datos de contacto para avisos transaccionales, solo con el consentimiento vigente (Ley 1581 de 2012). */
interface CustomerContacts
{
    /** Null si el cliente no tiene teléfono o su último consentimiento de tratamiento de datos no está otorgado. */
    public function whatsApp(int $customerId): ?CustomerWhatsApp;
}
