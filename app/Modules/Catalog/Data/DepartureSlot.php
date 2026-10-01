<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Data;

/** Salida de un producto en una fecha, con su hora local y cupo disponible. */
final readonly class DepartureSlot
{
    public function __construct(
        public string $ulid,
        public string $startsAt,
        public int $availableSeats,
        public bool $isOpen,
    ) {}
}
