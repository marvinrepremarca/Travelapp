<?php

declare(strict_types=1);

namespace App\Modules\Shared\IntegrationEvents;

use App\Modules\Shared\Enums\Capability;
use App\Modules\Shared\Enums\CatchUpPolicy;

/** Un listener de una capacidad que reacciona a un evento de integración de otra. */
final readonly class Subscription
{
    private const METHOD_SEPARATOR = '@';

    /**
     * @param  class-string<IntegrationEvent>  $event
     * @param  class-string  $listener
     */
    public function __construct(
        public Capability $capability,
        public string $event,
        public string $listener,
        public string $method,
        public CatchUpPolicy $catchUp,
    ) {}

    /** Identificador estable del suscriptor en la tabla de entregas. */
    public function key(): string
    {
        return $this->listener . self::METHOD_SEPARATOR . $this->method;
    }
}
