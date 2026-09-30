<?php

declare(strict_types=1);

namespace App\Modules\Crm\Exceptions;

use App\Modules\Crm\Enums\LeadStatus;
use App\Modules\Shared\Exceptions\BusinessRuleException;

final class LeadRuleViolation extends BusinessRuleException
{
    public static function transition(LeadStatus $from, LeadStatus $to): self
    {
        return new self(__('crm.errors.lead_transition', ['from' => $from->label(), 'to' => $to->label()]));
    }

    public static function lostReasonRequired(): self
    {
        return new self(__('crm.errors.lost_reason_required'));
    }

    public static function customerRequired(): self
    {
        return new self(__('crm.errors.lead_customer_required'));
    }

    public static function contactRequired(): self
    {
        return new self(__('crm.errors.lead_contact_required'));
    }

    public function errorCode(): string
    {
        return 'invalid_lead';
    }
}
