<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Enums;

/** Origen del costo neto del ítem. */
enum QuoteItemKind: string
{
    /** Producto o paquete del catálogo propio: el neto sale de sus tarifas. */
    case Catalog = 'catalog';
    /** Servicio de un proveedor con neto digitado por el asesor (hotel, vuelo…) hasta integrar Amadeus. */
    case Manual = 'manual';

    public function label(): string
    {
        return __("quotes.item_kind.{$this->value}");
    }
}
