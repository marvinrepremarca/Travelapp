<?php

declare(strict_types=1);

namespace App\Modules\Crm\Actions;

use App\Modules\Crm\Enums\InteractionType;
use App\Modules\Crm\Enums\LeadStatus;
use App\Modules\Crm\Models\Lead;
use App\Modules\Crm\Models\LeadInteraction;
use App\Modules\Identity\Models\User;
use Carbon\CarbonImmutable;

/** Registra un contacto con el prospecto. El primer contacto de un lead nuevo lo pasa a "contactado". */
final readonly class LogLeadInteractionAction
{
    public function __construct(private MoveLeadAction $move) {}

    public function execute(Lead $lead, InteractionType $type, string $summary, User $actor, ?CarbonImmutable $occurredAt = null): LeadInteraction
    {
        $interaction = $lead->interactions()->create([
            'type' => $type,
            'summary' => $summary,
            'occurred_at' => $occurredAt ?? CarbonImmutable::now(),
            'user_id' => $actor->id,
        ]);

        if ($lead->status === LeadStatus::New && $type !== InteractionType::Note) {
            $this->move->execute($lead, LeadStatus::Contacted, $actor);
        }

        return $interaction;
    }
}
