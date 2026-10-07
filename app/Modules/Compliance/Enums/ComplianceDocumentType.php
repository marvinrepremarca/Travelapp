<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Enums;

/** Documentos legales de la agencia con vigencia: RNT, pólizas, registro mercantil, licencias. */
enum ComplianceDocumentType: string
{
    case Rnt = 'rnt';
    case LiabilityPolicy = 'liability_policy';
    case ComplianceBond = 'compliance_bond';
    case CommercialRegistry = 'commercial_registry';
    case OperatingLicense = 'operating_license';
    case Other = 'other';

    public function label(): string
    {
        return __("compliance.document_type.{$this->value}");
    }
}
