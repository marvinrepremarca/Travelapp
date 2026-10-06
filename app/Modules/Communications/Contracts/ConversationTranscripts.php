<?php

declare(strict_types=1);

namespace App\Modules\Communications\Contracts;

use App\Modules\Communications\Data\TranscriptLine;

/** Lo que ve el contacto en su WhatsApp (para el simulador de la demo). */
interface ConversationTranscripts
{
    /** @return list<TranscriptLine> */
    public function forPhone(string $phone, int $limit): array;
}
