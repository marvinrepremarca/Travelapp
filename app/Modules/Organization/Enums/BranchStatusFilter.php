<?php

declare(strict_types=1);

namespace App\Modules\Organization\Enums;

enum BranchStatusFilter: string
{
    case All = 'all';
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return __("organization.branches.filters.{$this->value}");
    }
}
