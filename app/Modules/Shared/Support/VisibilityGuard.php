<?php

declare(strict_types=1);

namespace App\Modules\Shared\Support;

use App\Modules\Shared\Contracts\ScopedViewer;
use App\Modules\Shared\Enums\VisibilityScope;

/**
 * Misma regla de alcance que `HasVisibilityScope`, para datos que llegan de otro módulo por contrato
 * (solo se conoce el responsable y la sucursal, no el modelo).
 */
final class VisibilityGuard
{
    public static function allows(ScopedViewer $viewer, int $ownerId, ?int $branchId): bool
    {
        return match ($viewer->visibilityScope()) {
            VisibilityScope::All => true,
            VisibilityScope::Branch => $viewer->viewerBranchId() !== null && $branchId === $viewer->viewerBranchId(),
            VisibilityScope::Own => $ownerId === $viewer->viewerId(),
        };
    }
}
