<?php

declare(strict_types=1);

namespace App\Modules\Communications\Services;

use App\Modules\Communications\Contracts\CustomerNotices;
use App\Modules\Communications\Enums\NoticeTemplate;

/** Mensajería apagada: no se envían avisos; quien llama recibe false, igual que sin WhatsApp autorizado. */
final class NullCustomerNotices implements CustomerNotices
{
    public function send(int $customerId, int $ownerId, ?int $branchId, NoticeTemplate $template, array $params, string $dedupeKey): bool
    {
        return false;
    }
}
