<?php

declare(strict_types=1);

namespace App\Modules\Communications\Data;

use App\Modules\Communications\Enums\BotStep;

/** Resultado de un turno del bot: qué responde, el paso siguiente, lo aprendido y si pasa a un asesor. */
final readonly class BotTurn
{
    /** @param  array<string, string|int|null>  $data */
    public function __construct(
        public string $reply,
        public BotStep $nextStep,
        public array $data,
        public bool $handoff,
    ) {}
}
