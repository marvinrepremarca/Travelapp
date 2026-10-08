<?php

declare(strict_types=1);

namespace App\Modules\Shared\Enums;

/**
 * Capacidades de negocio que se encienden o apagan por despliegue (ADR-0007). El núcleo no figura: siempre está activo.
 * Etiquetas en lang/es/capabilities.php; interruptores en config/capabilities.php.
 */
enum Capability: string
{
    case Commercial = 'commercial';
    case Quoting = 'quoting';
    case OwnProduct = 'own_product';
    case Bookings = 'bookings';
    case Operations = 'operations';
    case Collections = 'collections';
    case Accounting = 'accounting';
    case Invoicing = 'invoicing';
    case Messaging = 'messaging';
    case Portals = 'portals';
    case Compliance = 'compliance';

    /**
     * Capacidades sin las que esta no tiene sentido. Solo dependencias de negocio inevitables:
     * lo demás se reconoce por eventos o se registra a mano.
     *
     * @return list<self>
     */
    public function requires(): array
    {
        return match ($this) {
            self::Portals => [self::Bookings],
            // Las salidas que se operan son las del catálogo propio.
            self::Operations => [self::OwnProduct],
            default => [],
        };
    }

    public function label(): string
    {
        return __("capabilities.names.{$this->value}");
    }
}
