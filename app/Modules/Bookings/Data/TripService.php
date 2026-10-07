<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Data;

use App\Modules\Bookings\Enums\BookingItemStatus;
use App\Modules\Shared\Enums\ProductType;
use Carbon\CarbonImmutable;

/** Servicio del viaje como lo ve el viajero (sin precios netos ni datos del proveedor). */
final readonly class TripService
{
    public function __construct(
        public string $ulid,
        public CarbonImmutable $serviceDate,
        public int $nights,
        public string $description,
        public ProductType $productType,
        public BookingItemStatus $status,
        public ?string $confirmationCode,
    ) {}

    public function hasVoucher(): bool
    {
        return $this->status === BookingItemStatus::Confirmed;
    }
}
