<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Contracts;

use App\Modules\Catalog\Exceptions\CatalogRuleViolation;

/** Cupos de las salidas del producto propio. Nunca hay sobreventa: sin cupo, la venta se rechaza. */
interface CatalogInventory
{
    /**
     * Aparta cupos en una salida. Repetir la misma `idempotencyKey` devuelve el mismo apartado sin descontar de nuevo.
     *
     * @return string ULID del apartado
     *
     * @throws CatalogRuleViolation salida no abierta o sin cupo suficiente
     */
    public function hold(string $departureUlid, int $seats, string $reference, string $idempotencyKey): string;

    /** Devuelve los cupos del apartado a la salida. Liberar dos veces no tiene efecto. */
    public function release(string $holdUlid): void;
}
