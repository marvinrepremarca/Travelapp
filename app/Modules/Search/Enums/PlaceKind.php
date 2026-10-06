<?php

declare(strict_types=1);

namespace App\Modules\Search\Enums;

/** Tipo de lugar que acepta un campo con autocompletar y el código que guarda. */
enum PlaceKind: string
{
    /** Aeropuerto → código IATA de 3 letras. */
    case Airport = 'airport';

    /** Ciudad → nombre de la ciudad y su país ISO. */
    case City = 'city';

    /** País → código ISO 3166-1 alfa-2. */
    case Country = 'country';
}
