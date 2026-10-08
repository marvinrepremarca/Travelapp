<?php

declare(strict_types=1);

namespace App\Modules\Shared\Capabilities;

use App\Modules\Shared\Enums\Capability;
use Illuminate\Support\Facades\Route;

/**
 * Estado de las capacidades de negocio en este despliegue (ADR-0007). Se lee una vez por proceso desde config:
 * cambiar una capacidad = cambiar `.env` y volver a desplegar.
 */
final class Capabilities
{
    public const MIDDLEWARE = 'capability';

    private const MIDDLEWARE_SEPARATOR = ':';

    private const LIST_SEPARATOR = ',';

    /** @var array<string, bool>|null */
    private ?array $states = null;

    public function enabled(Capability $capability): bool
    {
        return $this->states()[$capability->value];
    }

    /**
     * Dependencias incumplidas, como texto legible para el despliegue.
     *
     * @return list<string>
     */
    public function problems(): array
    {
        $problems = [];
        foreach (Capability::cases() as $capability) {
            if (! $this->enabled($capability)) {
                continue;
            }

            foreach ($capability->requires() as $required) {
                if (! $this->enabled($required)) {
                    $problems[] = __('capabilities.missing_requirement', ['capability' => $capability->label(), 'required' => $required->label()]);
                }
            }
        }

        return $problems;
    }

    /** @throws CapabilityConfigurationInvalid */
    public function assertConsistent(): void
    {
        $problems = $this->problems();
        if ($problems !== []) {
            throw CapabilityConfigurationInvalid::because($problems);
        }
    }

    /** Middleware que responde 404 si alguna de las capacidades está apagada: `capability:accounting,invoicing`. */
    public static function middleware(Capability ...$capabilities): string
    {
        return self::MIDDLEWARE . self::MIDDLEWARE_SEPARATOR
            . implode(self::LIST_SEPARATOR, array_map(static fn(Capability $c): string => $c->value, $capabilities));
    }

    /** ¿La ruta existe y todas sus capacidades están encendidas? Para menús y enlaces entre pantallas. */
    public function allowsRoute(string $name): bool
    {
        $route = Route::getRoutes()->getByName($name);
        if ($route === null) {
            return false;
        }

        $prefix = self::MIDDLEWARE . self::MIDDLEWARE_SEPARATOR;
        foreach ($route->gatherMiddleware() as $middleware) {
            if (! is_string($middleware) || ! str_starts_with($middleware, $prefix)) {
                continue;
            }

            foreach (explode(self::LIST_SEPARATOR, substr($middleware, strlen($prefix))) as $value) {
                if (! $this->enabled(Capability::from($value))) {
                    return false;
                }
            }
        }

        return true;
    }

    /** @return array<string, bool> */
    private function states(): array
    {
        if ($this->states === null) {
            $this->states = [];
            foreach (Capability::cases() as $capability) {
                $this->states[$capability->value] = config()->boolean("capabilities.enabled.{$capability->value}");
            }
        }

        return $this->states;
    }
}
