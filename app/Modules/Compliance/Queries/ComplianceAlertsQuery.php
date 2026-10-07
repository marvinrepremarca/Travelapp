<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Queries;

use App\Modules\Compliance\Enums\DataRequestStatus;
use App\Modules\Compliance\Models\ComplianceDocument;
use App\Modules\Compliance\Models\ComplianceObligation;
use App\Modules\Compliance\Models\DataSubjectRequest;
use App\Modules\Identity\Models\User;
use App\Modules\Organization\Contracts\HolidayCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/** Alertas de vencimiento (documentos, obligaciones y solicitudes de titulares) y calendario de próximos vencimientos. */
final readonly class ComplianceAlertsQuery
{
    public function __construct(private HolidayCalendar $calendar) {}

    /** @return array{expired_documents: int, expiring_documents: int, overdue_obligations: int, due_obligations: int, overdue_requests: int, due_requests: int} */
    public function counts(CarbonImmutable $today): array
    {
        $documentLimit = $today->addDays(config()->integer('travel.compliance.document_alert_days'))->toDateString();
        $obligationLimit = $today->addDays(config()->integer('travel.compliance.obligation_alert_days'))->toDateString();
        $requestLimit = $this->calendar->addBusinessDays($today, config()->integer('travel.compliance.request_alert_business_days'))->toDateString();
        $date = $today->toDateString();

        $documents = ComplianceDocument::query()
            ->selectRaw('SUM(CASE WHEN expires_on < ? THEN 1 ELSE 0 END) AS expired, SUM(CASE WHEN expires_on >= ? AND expires_on <= ? THEN 1 ELSE 0 END) AS expiring', [$date, $date, $documentLimit])
            ->toBase()->first();
        $obligations = ComplianceObligation::query()->whereNull('completed_at')
            ->selectRaw('SUM(CASE WHEN due_on < ? THEN 1 ELSE 0 END) AS overdue, SUM(CASE WHEN due_on >= ? AND due_on <= ? THEN 1 ELSE 0 END) AS due', [$date, $date, $obligationLimit])
            ->toBase()->first();
        $requests = DataSubjectRequest::query()->whereIn('status', DataRequestStatus::open())
            ->selectRaw('SUM(CASE WHEN due_on < ? THEN 1 ELSE 0 END) AS overdue, SUM(CASE WHEN due_on >= ? AND due_on <= ? THEN 1 ELSE 0 END) AS due', [$date, $date, $requestLimit])
            ->toBase()->first();

        return [
            'expired_documents' => (int) ($documents->expired ?? 0),
            'expiring_documents' => (int) ($documents->expiring ?? 0),
            'overdue_obligations' => (int) ($obligations->overdue ?? 0),
            'due_obligations' => (int) ($obligations->due ?? 0),
            'overdue_requests' => (int) ($requests->overdue ?? 0),
            'due_requests' => (int) ($requests->due ?? 0),
        ];
    }

    /**
     * Calendario: obligaciones pendientes y vencimientos de documentos hasta N días ⚙ (incluye lo ya vencido), por fecha.
     *
     * @return Collection<int, array{date: CarbonImmutable, kind: string, title: string, responsible: string, obligation: ComplianceObligation|null, href: string}>
     */
    public function calendar(CarbonImmutable $today): Collection
    {
        $until = $today->addDays(config()->integer('travel.compliance.calendar_days'))->toDateString();

        $obligations = ComplianceObligation::query()->whereNull('completed_at')->where('due_on', '<=', $until)->orderBy('due_on')->get();
        $documents = ComplianceDocument::query()->where('expires_on', '<=', $until)->orderBy('expires_on')->get();
        // Los responsables de ambos listados se leen en una sola consulta.
        $names = User::query()
            ->whereIn('id', $obligations->pluck('responsible_id')->merge($documents->pluck('responsible_id'))->unique()->all())
            ->pluck('name', 'id');

        $obligationEntries = $obligations->map(static fn(ComplianceObligation $obligation): array => [
            'date' => $obligation->due_on,
            'kind' => 'obligation',
            'title' => $obligation->title,
            'responsible' => (string) $names->get($obligation->responsible_id),
            'obligation' => $obligation,
            'href' => route('compliance.obligations.edit', $obligation),
        ]);
        $documentEntries = $documents->map(static fn(ComplianceDocument $document): array => [
            'date' => $document->expires_on,
            'kind' => 'document',
            'title' => (string) __('compliance.calendar.document_expires', ['type' => $document->type->label(), 'number' => $document->number]),
            'responsible' => (string) $names->get($document->responsible_id),
            'obligation' => null,
            'href' => route('compliance.documents.edit', $document),
        ]);

        return $obligationEntries->toBase()->merge($documentEntries)->sortBy(static fn(array $entry): string => $entry['date']->toDateString())->values();
    }
}
