<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Enums;

/** Derechos del titular de datos (Ley 1581 de 2012): conocer, actualizar/rectificar, suprimir, revocar la autorización o reclamar. */
enum DataRequestType: string
{
    case Access = 'access';
    case Rectification = 'rectification';
    case Deletion = 'deletion';
    case Revocation = 'revocation';
    case Complaint = 'complaint';

    public function label(): string
    {
        return __("compliance.request_type.{$this->value}");
    }
}
