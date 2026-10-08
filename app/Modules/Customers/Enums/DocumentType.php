<?php

declare(strict_types=1);

namespace App\Modules\Customers\Enums;

/** Tipos de documento de identificación (Colombia primero; extensible por país). */
enum DocumentType: string
{
    case CitizenId = 'cc';
    case ForeignerId = 'ce';
    case Passport = 'passport';
    case TemporaryProtectionPermit = 'ppt';
    case IdentityCard = 'ti';
    case CivilRegistry = 'rc';
    case Nit = 'nit';
    case ForeignTaxId = 'foreign_tax_id';

    public function label(): string
    {
        return __("customers.document_type.{$this->value}");
    }

    /** Normaliza para comparar: sin espacios, puntos ni guiones, en mayúsculas. */
    public function normalize(string $number): string
    {
        return mb_strtoupper((string) preg_replace('/[\s.\-]/', '', $number));
    }
}
