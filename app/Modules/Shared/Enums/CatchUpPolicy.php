<?php

declare(strict_types=1);

namespace App\Modules\Shared\Enums;

/** Qué hace un suscriptor con los eventos ocurridos mientras su capacidad estuvo apagada (ADR-0007). */
enum CatchUpPolicy: string
{
    /** Los procesa al encenderse, en orden: registros contables, facturas, decisiones. */
    case Replay = 'replay';

    /** Los descarta: avisos al cliente que ya no tienen sentido fuera de su momento. */
    case Skip = 'skip';
}
