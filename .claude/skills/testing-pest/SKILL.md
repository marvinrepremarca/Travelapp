---
name: testing-pest
description: Estrategia y ejemplos de pruebas con Pest 3 (unit, feature, Livewire, arquitectura, contratos de adaptadores de proveedores, pruebas de reglas de precio y estados, alcance de visibilidad). Úsala al escribir o corregir tests, al definir qué probar de una funcionalidad o al revisar cobertura.
---

# Testing con Pest

## Pirámide
| Tipo | Qué cubre | Dónde |
|---|---|---|
| Unit | Servicios puros: `PriceCalculator`, `CancellationPenaltyCalculator`, `BookingStatusResolver`, value objects, enums de estado | `tests/Unit/<Modulo>` |
| Feature | Actions, endpoints web/API, componentes Livewire, jobs, listeners, políticas | `tests/Feature/<Modulo>` |
| Contract | Cada adaptador de proveedor contra el contrato de su puerto, con fixtures | `tests/Contract/<Puerto>` |
| Arch | Límites de módulos, convenciones | `tests/Arch` |
| E2E (pocos) | Flujos críticos: cotizar→reservar→pagar; checkout B2C | Pest browser testing, cuando se apruebe |

Cobertura mínima 90 % en `app/Modules`; 100 % de ramas en precios, penalidades, estados y ledger.

## Qué probar según lo que construyes
- **Action:** caso feliz, cada regla de negocio violada (excepción concreta), efectos (eventos, auditoría, ledger), idempotencia.
- **Endpoint/Livewire:** invitado, sin permiso, fuera de alcance (404), validación, éxito; que el precio enviado por el cliente se ignora.
- **Máquina de estados:** dataset de todas las transiciones válidas e inválidas.
- **Precios:** datasets de reglas, temporadas, edades, monedas, redondeos.
- **Adaptador:** ver skill `supplier-integrations`.
- **Job programado:** con `travelTo` alrededor de cada umbral y zonas horarias distintas.

## Ejemplos
```php
it('rejects cancellation when the item is already cancelled', function (): void {
    $item = BookingItem::factory()->cancelled()->create();

    $cancel = fn () => app(CancelBookingItemAction::class)->execute($item, CancellationData::fake(), agent());

    expect($cancel)->toThrow(InvalidBookingItemTransition::class);
});

it('hides bookings owned by another agent when the scope is own', function (): void {
    $foreign = Booking::factory()->ownedBy(agent())->create();

    actingAs(agent())->get(route('bookings.show', $foreign))->assertNotFound();
});

it('charges the supplier penalty that applies at the cancellation time', function (string $now, int $expectedPenalty): void {
    travelTo(CarbonImmutable::parse($now, 'America/Bogota'));
    $item = BookingItem::factory()->withPolicy(freeUntil: '2026-10-12 23:59', thenPercent: 50)->netUsd(20000)->create();

    expect(app(CancellationPenaltyCalculator::class)->for($item, now())->penalty->getMinorAmount()->toInt())->toBe($expectedPenalty);
})->with([
    'just before cut-off' => ['2026-10-12 23:58', 0],
    'just after cut-off' => ['2026-10-13 00:00', 10000],
]);
```

## Configuración
`tests/Pest.php`: helpers `agent()`, `financeUser()`, `traveler()`, `branchManager()`; `RefreshDatabase` (o `LazilyRefreshDatabase`); `Http::preventStrayRequests()` global; zona horaria y locale fijos; `Model::shouldBeStrict()`.

## Checklist de cumplimiento
- [ ] Tests en inglés, AAA, un comportamiento por test, con factories y states.
- [ ] Feliz, reglas violadas, 403, fuera de alcance (404) y validación cubiertos.
- [ ] Datasets para matrices de estados, precios, fechas y roles.
- [ ] Tiempo controlado (`travelTo`/`freezeTime`), zonas horarias y DST probados.
- [ ] `Http::preventStrayRequests()`; fixtures anonimizadas; ninguna llamada real.
- [ ] Cobertura ≥ 90 % (100 % de ramas en precios, penalidades, estados y ledger).
- [ ] Ningún test debilitado o borrado para pasar.
