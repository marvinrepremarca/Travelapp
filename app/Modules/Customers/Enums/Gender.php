<?php

declare(strict_types=1);

namespace App\Modules\Customers\Enums;

/** Sexo según el documento de viaje (lo exigen aerolíneas y migración). */
enum Gender: string
{
    case Female = 'F';
    case Male = 'M';
    case Unspecified = 'X';

    public function label(): string
    {
        return __("customers.gender.{$this->value}");
    }
}
