<?php

declare(strict_types=1);

namespace App\Modules\Customers\Actions;

use App\Modules\Audit\Contracts\SensitiveDataAccessRecorder;
use App\Modules\Audit\Enums\SensitiveDataAccessType;
use App\Modules\Customers\Enums\SensitiveCustomerField;
use App\Modules\Customers\Exceptions\CustomerRuleViolation;
use App\Modules\Customers\Models\Customer;
use App\Modules\Identity\Models\User;
use App\Modules\Shared\Enums\Permission;

/** Muestra un dato sensible en claro solo con permiso y deja constancia del acceso. */
final readonly class RevealCustomerFieldAction
{
    private const DATE_FORMAT = 'Y-m-d';

    public function __construct(private SensitiveDataAccessRecorder $recorder) {}

    public function execute(Customer $customer, SensitiveCustomerField $field, User $actor, string $reason): string
    {
        if (! $actor->can(Permission::SensitiveDataView->value)) {
            throw CustomerRuleViolation::sensitiveDataForbidden();
        }

        $this->recorder->record($actor, $customer, $field->value, SensitiveDataAccessType::Viewed, $reason);

        return match ($field) {
            SensitiveCustomerField::DocumentNumber => $customer->document_number,
            SensitiveCustomerField::BirthDate => (string) $customer->birth_date?->format(self::DATE_FORMAT),
        };
    }
}
