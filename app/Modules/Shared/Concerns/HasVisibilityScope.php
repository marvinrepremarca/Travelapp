<?php

declare(strict_types=1);

namespace App\Modules\Shared\Concerns;

use App\Modules\Shared\Contracts\ScopedViewer;
use App\Modules\Shared\Enums\VisibilityScope;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Modelos operativos con columnas `owner_id` y `branch_id`.
 * Los listados usan `visibleTo()` y las Policies `isVisibleTo()`.
 */
trait HasVisibilityScope
{
    /** @param Builder<static> $query */
    #[Scope]
    protected function visibleTo(Builder $query, ScopedViewer $viewer): void
    {
        match ($viewer->visibilityScope()) {
            VisibilityScope::All => null,
            VisibilityScope::Branch => $query->where($this->qualifyColumn($this->branchColumn()), $viewer->viewerBranchId()),
            VisibilityScope::Own => $query->where($this->qualifyColumn($this->ownerColumn()), $viewer->viewerId()),
        };
    }

    public function isVisibleTo(ScopedViewer $viewer): bool
    {
        return match ($viewer->visibilityScope()) {
            VisibilityScope::All => true,
            VisibilityScope::Branch => $viewer->viewerBranchId() !== null
                && $this->getAttribute($this->branchColumn()) === $viewer->viewerBranchId(),
            VisibilityScope::Own => $this->getAttribute($this->ownerColumn()) === $viewer->viewerId(),
        };
    }

    protected function ownerColumn(): string
    {
        return 'owner_id';
    }

    protected function branchColumn(): string
    {
        return 'branch_id';
    }
}
