<?php

declare(strict_types=1);

namespace App\Modules\Shared\ValueObjects;

use App\Modules\Shared\Enums\PassengerType;
use App\Modules\Shared\Exceptions\InvalidPassengerMix;

/**
 * Composición de pasajeros de una búsqueda o reserva.
 * Las edades de los menores son a la fecha del servicio, no a hoy.
 */
final readonly class PassengerMix
{
    /** @param list<int> $childAges */
    public function __construct(
        public int $adults,
        public array $childAges = [],
        public int $infants = 0,
    ) {
        if ($adults < 0 || $infants < 0 || array_filter($childAges, static fn(int $age): bool => $age < 0) !== []) {
            throw InvalidPassengerMix::negativeCount();
        }

        if ($adults === 0) {
            throw InvalidPassengerMix::noAdults();
        }

        // Un infante viaja en brazos de un adulto: máximo uno por adulto.
        if ($infants > $adults) {
            throw InvalidPassengerMix::tooManyInfants($infants, $adults);
        }
    }

    /** @param list<int> $agesAtService */
    public static function fromAges(array $agesAtService): self
    {
        $adults = 0;
        $infants = 0;
        $childAges = [];

        foreach ($agesAtService as $age) {
            match (PassengerType::forAge($age)) {
                PassengerType::Adult => $adults++,
                PassengerType::Infant => $infants++,
                PassengerType::Child => $childAges[] = $age,
            };
        }

        return new self($adults, $childAges, $infants);
    }

    public function children(): int
    {
        return count($this->childAges);
    }

    public function total(): int
    {
        return $this->adults + $this->children() + $this->infants;
    }

    /** Pasajeros que ocupan asiento o cama (los infantes no). */
    public function occupying(): int
    {
        return $this->adults + $this->children();
    }

    public function countOf(PassengerType $type): int
    {
        return match ($type) {
            PassengerType::Adult => $this->adults,
            PassengerType::Child => $this->children(),
            PassengerType::Infant => $this->infants,
        };
    }
}
