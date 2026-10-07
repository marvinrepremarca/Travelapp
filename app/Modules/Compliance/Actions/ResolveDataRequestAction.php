<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Actions;

use App\Modules\Compliance\Enums\DataRequestStatus;
use App\Modules\Compliance\Exceptions\ComplianceRuleViolation;
use App\Modules\Compliance\Models\DataSubjectRequest;
use App\Modules\Identity\Models\User;
use Carbon\CarbonImmutable;

/** Avanza la solicitud: en trámite, o la cierra (respondida / rechazada) con la respuesta enviada al titular. */
final readonly class ResolveDataRequestAction
{
    public function execute(User $actor, DataSubjectRequest $request, DataRequestStatus $status, ?string $response): void
    {
        throw_unless($request->status->isOpen(), ComplianceRuleViolation::requestClosed());
        throw_if($status === DataRequestStatus::Received, ComplianceRuleViolation::invalidTransition());
        throw_if(! $status->isOpen() && ($response === null || trim($response) === ''), ComplianceRuleViolation::responseRequired());

        $request->status = $status;
        if (! $status->isOpen()) {
            $request->response = $response;
            $request->resolved_at = CarbonImmutable::now();
            $request->resolved_by = $actor->id;
        }
        $request->save();
    }
}
