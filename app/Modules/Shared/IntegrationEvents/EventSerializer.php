<?php

declare(strict_types=1);

namespace App\Modules\Shared\IntegrationEvents;

use BackedEnum;
use Carbon\CarbonImmutable;
use LogicException;
use ReflectionClass;
use ReflectionNamedType;

/** Convierte un evento de integración en JSON y de vuelta, a partir de los parámetros de su constructor. */
final class EventSerializer
{
    /** @return array<string, mixed> */
    public function toPayload(IntegrationEvent $event): array
    {
        $payload = [];
        foreach ($this->parameters($event::class) as $name => $type) {
            $value = $event->{$name};
            $payload[$name] = match (true) {
                $value instanceof BackedEnum => $value->value,
                $value instanceof CarbonImmutable => $value->toIso8601ZuluString('microsecond'),
                default => $value,
            };
        }

        return $payload;
    }

    /**
     * @template T of IntegrationEvent
     *
     * @param  class-string<T>  $class
     * @param  array<string, mixed>  $payload
     * @return T
     */
    public function fromPayload(string $class, array $payload): IntegrationEvent
    {
        $arguments = [];
        foreach ($this->parameters($class) as $name => $type) {
            $value = $payload[$name] ?? null;
            $arguments[$name] = match (true) {
                $value === null => null,
                is_subclass_of($type, BackedEnum::class) => $type::from($value),
                $type === CarbonImmutable::class => CarbonImmutable::parse($value),
                default => $value,
            };
        }

        return new $class(...$arguments);
    }

    /**
     * @param  class-string  $class
     * @return array<string, string>
     */
    private function parameters(string $class): array
    {
        $constructor = (new ReflectionClass($class))->getConstructor();
        if ($constructor === null) {
            return [];
        }

        $parameters = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if (! $type instanceof ReflectionNamedType) {
                throw new LogicException("Integration event {$class} needs a single named type on \${$parameter->getName()}.");
            }

            $parameters[$parameter->getName()] = $type->getName();
        }

        return $parameters;
    }
}
