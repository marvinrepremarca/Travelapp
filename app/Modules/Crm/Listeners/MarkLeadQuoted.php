<?php

declare(strict_types=1);

namespace App\Modules\Crm\Listeners;

use App\Modules\Crm\Enums\LeadStatus;
use App\Modules\Crm\Models\Lead;
use App\Modules\Quotes\Events\QuoteSent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Cotización enviada desde un lead → el lead pasa a Cotizado. Es la única forma de llegar a ese estado.
 * Idempotente: reenvíos o reprocesos no cambian un lead ya cotizado, ganado o perdido.
 */
final class MarkLeadQuoted
{
    public function handle(QuoteSent $event): void
    {
        if ($event->leadUlid === null) {
            return;
        }

        DB::transaction(static function () use ($event): void {
            $lead = Lead::query()->where('ulid', $event->leadUlid)->lockForUpdate()->first();
            if (! $lead instanceof Lead || ! $lead->status->canBeQuoted()) {
                return;
            }

            $lead->status = LeadStatus::Quoted;
            $lead->status_changed_at = CarbonImmutable::now();
            $lead->save();
        });
    }
}
