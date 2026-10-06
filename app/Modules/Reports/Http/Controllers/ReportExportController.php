<?php

declare(strict_types=1);

namespace App\Modules\Reports\Http\Controllers;

use App\Modules\Identity\Models\User;
use App\Modules\Organization\Models\Branch;
use App\Modules\Reports\Data\Period;
use App\Modules\Reports\Data\ReportExport;
use App\Modules\Reports\Queries\ReceivablesQuery;
use App\Modules\Reports\Queries\SalesQuery;
use App\Modules\Shared\Enums\AuditLogName;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Descarga CSV de un reporte, dentro del alcance de quien la pide. Protegido contra inyección de fórmulas
 * (celdas que empiezan por = + - @ se anteponen con ') y auditado. Sin datos personales sensibles.
 */
final class ReportExportController
{
    private const UTF8_BOM = "\xEF\xBB\xBF";

    private const FORMULA_PREFIXES = ['=', '+', '-', '@', "\t", "\r"];

    private const SAFE_PREFIX = "'";

    public function __invoke(Request $request, string $report, SalesQuery $sales, ReceivablesQuery $receivables): StreamedResponse
    {
        $export = ReportExport::tryFrom($report) ?? abort(404);
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can($export->permission()->value), 403);

        $period = Period::month((string) $request->query('month', ''), CarbonImmutable::now());
        $rows = match ($export) {
            ReportExport::SalesByOwner => $this->salesByOwner($sales, $user, $period),
            ReportExport::SalesByBranch => $this->salesByBranch($sales, $user, $period),
            ReportExport::Receivables => $this->receivables($receivables, $user),
        };

        activity(AuditLogName::Reports->value)->causedBy($user)->withProperties(['report' => $export->value, 'month' => $period->key(), 'rows' => count($rows) - 1])->log('report_exported');

        $fileName = $export->value . '-' . $period->key() . '.csv';

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                return;
            }

            fwrite($output, self::UTF8_BOM);
            foreach ($rows as $row) {
                fputcsv($output, array_map($this->safe(...), $row), escape: '');
            }

            fclose($output);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return list<list<string>> */
    private function salesByOwner(SalesQuery $sales, User $user, Period $period): array
    {
        $report = $sales->for($user, $period);
        $names = User::query()->whereIn('id', array_keys($report->byOwner))->pluck('name', 'id');
        $rows = [[__('reports.owner'), __('reports.kpi.bookings'), __('reports.kpi.sales'), __('reports.kpi.margin'), __('reports.csv.margin_rate'), __('reports.csv.currency')]];
        foreach ($report->byOwner as $ownerId => $figures) {
            $rows[] = [(string) ($names[$ownerId] ?? $ownerId), (string) $figures->bookings, $this->amount($figures->sale), $this->amount($figures->margin()), (string) $figures->marginRate(), $report->currency];
        }

        return array_slice($rows, 0, config()->integer('travel.reports.export_max_rows') + 1);
    }

    /** @return list<list<string>> */
    private function salesByBranch(SalesQuery $sales, User $user, Period $period): array
    {
        $report = $sales->for($user, $period);
        $names = Branch::query()->whereIn('id', array_keys($report->byBranch))->pluck('name', 'id');
        $rows = [[__('reports.branch'), __('reports.kpi.bookings'), __('reports.kpi.sales'), __('reports.kpi.margin'), __('reports.csv.margin_rate'), __('reports.csv.currency')]];
        foreach ($report->byBranch as $branchId => $figures) {
            $rows[] = [(string) ($names[$branchId] ?? __('reports.no_branch')), (string) $figures->bookings, $this->amount($figures->sale), $this->amount($figures->margin()), (string) $figures->marginRate(), $report->currency];
        }

        return array_slice($rows, 0, config()->integer('travel.reports.export_max_rows') + 1);
    }

    /** @return list<list<string>> */
    private function receivables(ReceivablesQuery $receivables, User $user): array
    {
        $rows = [[__('reports.csv.booking'), __('reports.csv.customer'), __('reports.csv.balance'), __('reports.csv.due_date')]];
        foreach ($receivables->for($user, CarbonImmutable::now(config()->string('travel.agency.timezone'))->startOfDay())['items'] as $item) {
            $rows[] = [$item->bookingNumber, $item->customerName, $this->amount($item->balance), (string) $item->dueDate?->toDateString()];
        }

        return array_slice($rows, 0, config()->integer('travel.reports.export_max_rows') + 1);
    }

    private function amount(Money $money): string
    {
        return (string) $money->getAmount();
    }

    /** Evita que Excel interprete el contenido como fórmula (CSV injection). */
    private function safe(string $value): string
    {
        return $value !== '' && in_array($value[0], self::FORMULA_PREFIXES, true) && ! is_numeric($value) ? self::SAFE_PREFIX . $value : $value;
    }
}
