<?php

declare(strict_types=1);

namespace App\Modules\Crm\Enums;

enum CustomerType: string
{
    case Person = 'person';
    case Company = 'company';

    public function label(): string
    {
        return __("crm.customer_type.{$this->value}");
    }

    /** @return list<DocumentType> */
    public function allowedDocuments(): array
    {
        return match ($this) {
            self::Person => [DocumentType::CitizenId, DocumentType::ForeignerId, DocumentType::Passport, DocumentType::TemporaryProtectionPermit, DocumentType::IdentityCard, DocumentType::CivilRegistry, DocumentType::Nit],
            self::Company => [DocumentType::Nit, DocumentType::ForeignTaxId],
        };
    }
}
