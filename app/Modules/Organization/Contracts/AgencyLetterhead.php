<?php

declare(strict_types=1);

namespace App\Modules\Organization\Contracts;

use App\Modules\Organization\Data\Letterhead;

/** Datos de membrete de la agencia para documentos (PDF, correos). */
interface AgencyLetterhead
{
    public function letterhead(): Letterhead;
}
