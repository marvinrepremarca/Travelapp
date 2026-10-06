<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Listeners;

use App\Modules\Identity\Models\User;
use App\Modules\Invoicing\Actions\IssueCreditNoteAction;
use App\Modules\Invoicing\Enums\AdjustmentStatus;
use App\Modules\Invoicing\Models\InvoiceAdjustment;
use App\Modules\Workflow\Enums\ApprovalStatus;
use App\Modules\Workflow\Enums\ApprovalType;
use App\Modules\Workflow\Events\ApprovalResolved;
use App\Modules\Workflow\Models\ApprovalRequest;
use Carbon\CarbonImmutable;

/** Aplica la decisión de finanzas: aprobada → se emite la nota crédito; rechazada → la solicitud se cierra. */
final readonly class ApplyCreditNoteDecision
{
    public function __construct(private IssueCreditNoteAction $issue) {}

    public function handle(ApprovalResolved $event): void
    {
        if ($event->type !== ApprovalType::InvoiceVoid || $event->subjectType !== (new InvoiceAdjustment())->getMorphClass()) {
            return;
        }

        $adjustment = InvoiceAdjustment::query()->whereKey($event->subjectId)->where('status', AdjustmentStatus::Requested)->first();
        if (! $adjustment instanceof InvoiceAdjustment) {
            return;
        }

        if ($event->status !== ApprovalStatus::Approved) {
            $adjustment->status = AdjustmentStatus::Rejected;
            $adjustment->decided_at = CarbonImmutable::now();
            $adjustment->save();

            return;
        }

        $approval = ApprovalRequest::query()->where('ulid', $event->approvalUlid)->firstOrFail();
        $this->issue->execute($adjustment->ulid, User::query()->findOrFail($approval->decided_by ?? $adjustment->requested_by), CarbonImmutable::now());
    }
}
