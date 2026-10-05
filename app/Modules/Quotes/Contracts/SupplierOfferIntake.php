<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Contracts;

use App\Modules\Identity\Models\User;
use App\Modules\Quotes\Data\QuoteItemData;
use App\Modules\Shared\Exceptions\BusinessRuleException;

/** Entrada de ofertas de proveedores (Search) a cotizaciones en borrador del alcance del usuario. */
interface SupplierOfferIntake
{
    /**
     * Borradores que el usuario puede editar, más recientes primero.
     *
     * @return array<string, string> ulid => "número · título"
     */
    public function draftsFor(User $actor): array;

    /**
     * Agrega la oferta a la primera opción del borrador; el precio de venta lo calcula Pricing.
     *
     * @throws BusinessRuleException cotización inexistente, fuera de alcance o no editable
     */
    public function add(string $quoteUlid, User $actor, QuoteItemData $data): void;
}
