<?php

declare(strict_types=1);

namespace App\Modules\Crm\Contracts;

use App\Modules\Crm\Data\LeadData;
use App\Modules\Identity\Models\User;

/** Registrar un lead desde otro canal (p. ej. una conversación de WhatsApp). Devuelve su ULID. */
interface LeadIntake
{
    public function register(LeadData $data, User $owner): string;

    /** ULID del cliente vinculado al lead; null si aún no tiene. */
    public function customerOf(string $leadUlid): ?string;
}
