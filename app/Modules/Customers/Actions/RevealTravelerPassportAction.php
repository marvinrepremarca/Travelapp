<?php

declare(strict_types=1);

namespace App\Modules\Customers\Actions;

use App\Modules\Audit\Contracts\SensitiveDataAccessRecorder;
use App\Modules\Audit\Enums\SensitiveDataAccessType;
use App\Modules\Customers\Exceptions\CustomerRuleViolation;
use App\Modules\Customers\Models\Traveler;
use App\Modules\Identity\Models\User;
use App\Modules\Shared\Enums\Permission;

final readonly class RevealTravelerPassportAction
{
    private const FIELD = 'passport_number';

    public function __construct(private SensitiveDataAccessRecorder $recorder) {}

    public function execute(Traveler $traveler, User $actor, string $reason): string
    {
        if (! $actor->can(Permission::SensitiveDataView->value)) {
            throw CustomerRuleViolation::sensitiveDataForbidden();
        }

        $this->recorder->record($actor, $traveler, self::FIELD, SensitiveDataAccessType::Viewed, $reason);

        return (string) $traveler->passport_number;
    }
}
