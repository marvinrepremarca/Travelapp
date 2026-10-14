<?php

declare(strict_types=1);

namespace App\Modules\Crm\Actions;

use App\Modules\Crm\Enums\LeadStatus;
use App\Modules\Crm\Enums\LostReason;
use App\Modules\Crm\Events\LeadWon;
use App\Modules\Crm\Exceptions\LeadRuleViolation;
use App\Modules\Crm\Models\Lead;
use App\Modules\Customers\Models\Customer;
use App\Modules\Identity\Models\User;
use App\Modules\Shared\Capabilities\Capabilities;
use App\Modules\Shared\Enums\Capability;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Avanza el lead en el embudo. Perder exige motivo; ganar exige un cliente
 * (existente y visible para quien lo gana) y emite LeadWon. "Cotizado" solo lo pone el envío de una
 * cotización del sistema (MarkLeadQuoted); a mano no se puede.
 */
final readonly class MoveLeadAction
{
    public function __construct(private Capabilities $capabilities) {}

    public function execute(Lead $lead, LeadStatus $next, User $actor, ?LostReason $lostReason = null, ?string $lostNote = null, ?Customer $customer = null): Lead
    {
        if ($next === LeadStatus::Quoted) {
            throw LeadRuleViolation::quotedOnlyByQuote();
        }

        if (! in_array($next, $lead->status->manualTransitions($this->capabilities->enabled(Capability::Quoting)), true)) {
            throw LeadRuleViolation::transition($lead->status, $next);
        }

        if ($next === LeadStatus::Lost && ! $lostReason instanceof LostReason) {
            throw LeadRuleViolation::lostReasonRequired();
        }

        if ($next === LeadStatus::Won && (! $customer instanceof Customer || ! $customer->isVisibleTo($actor))) {
            throw LeadRuleViolation::customerRequired();
        }

        return DB::transaction(function () use ($lead, $next, $lostReason, $lostNote, $customer): Lead {
            $lead->status = $next;
            $lead->status_changed_at = CarbonImmutable::now();
            $lead->lost_reason = $next === LeadStatus::Lost ? $lostReason : null;
            $lead->lost_note = $next === LeadStatus::Lost ? $lostNote : null;

            if ($next === LeadStatus::Won) {
                $lead->customer_id = $customer->id;
            }

            $lead->save();

            if ($next === LeadStatus::Won) {
                (new LeadWon($lead->ulid, $customer->ulid, $lead->owner_id))->publish();
            }

            return $lead;
        });
    }
}
