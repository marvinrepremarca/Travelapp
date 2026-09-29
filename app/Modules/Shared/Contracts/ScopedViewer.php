<?php

declare(strict_types=1);

namespace App\Modules\Shared\Contracts;

use App\Modules\Shared\Enums\VisibilityScope;

/** Quien consulta datos operativos: define su alcance y su sucursal. */
interface ScopedViewer
{
    public function visibilityScope(): VisibilityScope;

    public function viewerId(): int;

    public function viewerBranchId(): ?int;
}
