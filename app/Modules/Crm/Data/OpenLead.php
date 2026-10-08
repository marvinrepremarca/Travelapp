<?php

declare(strict_types=1);

namespace App\Modules\Crm\Data;

use App\Modules\Crm\Enums\LeadStatus;

/** Prospecto pendiente de atención en el tablero del asesor. */
final readonly class OpenLead
{
    public function __construct(
        public string $ulid,
        public string $contactName,
        public ?string $destination,
        public LeadStatus $status,
    ) {}
}
