<?php

declare(strict_types=1);

namespace App\Modules\Communications\Contracts;

use App\Modules\Communications\Enums\NoticeTemplate;

/** Enviar un aviso con plantilla por WhatsApp a un cliente (con su consentimiento), una vez por clave. */
interface CustomerNotices
{
    /**
     * @param  array<string, string>  $params
     * @return bool  false si el cliente no tiene WhatsApp autorizado o el aviso ya se había enviado
     */
    public function send(int $customerId, int $ownerId, ?int $branchId, NoticeTemplate $template, array $params, string $dedupeKey): bool;
}
