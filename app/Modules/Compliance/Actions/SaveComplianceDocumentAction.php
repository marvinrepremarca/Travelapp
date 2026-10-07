<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Actions;

use App\Modules\Compliance\Data\DocumentData;
use App\Modules\Compliance\Models\ComplianceDocument;
use App\Modules\Compliance\Services\ComplianceTasks;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;

/** Registra o renueva un documento legal; si cambia el vencimiento, programa la tarea de renovación con alerta previa ⚙. */
final readonly class SaveComplianceDocumentAction
{
    public function __construct(private ComplianceTasks $tasks) {}

    public function execute(User $actor, DocumentData $data, ?ComplianceDocument $document = null): ComplianceDocument
    {
        return DB::transaction(function () use ($actor, $data, $document): ComplianceDocument {
            $document ??= new ComplianceDocument();
            $document->fill([
                'type' => $data->type,
                'number' => $data->number,
                'issuer' => $data->issuer,
                'starts_on' => $data->startsOn,
                'expires_on' => $data->expiresOn,
                'responsible_id' => $data->responsibleId,
                'notes' => $data->notes,
            ]);
            $isNewDeadline = ! $document->exists || $document->isDirty('expires_on');
            if (! $document->exists) {
                $document->created_by = $actor->id;
            }
            $document->save();

            if ($isNewDeadline) {
                $this->tasks->schedule(
                    __('compliance.documents.renewal_task', ['type' => $data->type->label(), 'number' => $data->number]),
                    $data->responsibleId,
                    $data->expiresOn,
                    config()->integer('travel.compliance.document_alert_days'),
                    $document,
                    $actor,
                );
            }

            return $document;
        });
    }
}
