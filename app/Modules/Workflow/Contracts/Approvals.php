<?php

declare(strict_types=1);

namespace App\Modules\Workflow\Contracts;

use App\Modules\Identity\Models\User;
use App\Modules\Workflow\Data\ApprovalRequestData;
use App\Modules\Workflow\Models\ApprovalRequest;

/** Punto de entrada para que otros módulos pidan aprobaciones. La respuesta llega por el evento ApprovalResolved. */
interface Approvals
{
    public function request(ApprovalRequestData $data, User $requester): ApprovalRequest;
}
