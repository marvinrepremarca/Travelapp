<?php

declare(strict_types=1);

namespace App\Modules\Crm\Enums;

enum SensitiveCustomerField: string
{
    case DocumentNumber = 'document_number';
    case BirthDate = 'birth_date';

    public function label(): string
    {
        return __("crm.sensitive_fields.{$this->value}");
    }
}
