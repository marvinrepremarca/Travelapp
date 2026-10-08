---
name: modular-architecture
description: Estructura interna y límites del monolito modular (app/Modules). Úsala al crear un módulo, decidir dónde va una clase, comunicar dos módulos (contratos, eventos), registrar providers, rutas, vistas, componentes Livewire o migraciones de un módulo, aplicar el alcance de visibilidad, o escribir tests de arquitectura.
---

# Monolito modular

¿Por qué no microservicios? Un solo equipo, transacciones de negocio que cruzan reserva-pago-factura y necesidad de consistencia. Los límites estrictos entre módulos permiten extraer un servicio después (p. ej. `Search` o `Integrations`) si la carga lo exige (ADR-0001).

## Estructura de un módulo (crea solo lo que necesites)

```
app/Modules/Bookings/
├── Actions/                 # CreateBookingAction, CancelBookingItemAction
├── Contracts/               # [PÚBLICO] BookingLookup
├── Data/                    # [PÚBLICO] DTOs readonly
├── Database/{Factories,Migrations,Seeders}/
├── Enums/                   # [PÚBLICO] BookingStatus, BookingItemStatus
├── Events/                  # [PÚBLICO] BookingConfirmed
├── Exceptions/
├── Http/{Controllers,Requests,Resources}/   # Resources = API JSON
├── Jobs/
├── Listeners/
├── Livewire/                # Componentes; namespace de vistas bookings::
├── Models/                  # [PÚBLICO solo lectura / relaciones]
├── Notifications/
├── Policies/
├── Providers/BookingsServiceProvider.php
├── Queries/
├── Resources/views/
├── Routes/{web,api}.php
├── Sagas/                   # Orquestaciones multi-paso con compensación
└── Services/                # BookingStatusResolver
```

Traducciones en `lang/es/<modulo>.php`. `Shared` solo contiene lo verdaderamente transversal.

## Límites

**API pública:** `Contracts/`, `Data/`, `Enums/`, `Events/`, `Models/` (relaciones y lectura).

**Prohibido:** usar `Actions`, `Services`, `Http`, `Jobs`, `Listeners`, `Policies`, `Queries`, `Livewire` o `Sagas` de otro módulo; escribir modelos de otro módulo (invoca su Contract).

**Comunicación:** síncrona por interfaz en `Contracts/` enlazada en el provider del módulo dueño; asíncrona por eventos (`ShouldDispatchAfterCommit`) con listeners en el receptor.

**Excepción:** `Reports` lee (SELECT) cualquier tabla; nunca escribe.

## Capacidades activables (ADR-0007)

- Cada módulo de negocio pertenece a una `Capability` (enum en Shared); el núcleo (Organization, Identity, Audit, Workflow, Pricing, Suppliers, Documents, clientes) no se apaga.
- Interruptores en `config/capabilities.php` (`CAPABILITY_<NOMBRE>_ENABLED`); `php artisan capabilities:status --check` en cada despliegue.
- Rutas: `PathPrefix::load($archivo, Capability::X)` o `Capabilities::middleware(Capability::X)` por grupo → 404 si está apagada. Los webhooks entrantes nunca se bloquean (`withoutMiddleware`).
- Menú y enlaces entre pantallas: `Capabilities::allowsRoute($nombre)`.
- Apagar nunca borra datos. Lo que una capacidad necesita de otra llega por eventos o por un `Contract` con implementación nula.
- Tests: `disableCapabilities(Capability::X)` en `tests/Pest.php`.

## Una agencia, sucursales y alcance de visibilidad (ADR-0002)

- No hay multi-tenancy: el sistema pertenece a una sola agencia. Sus datos y configuración viven en `Organization`.
- Tablas operativas con `branch_id` y `owner_id`. Trait `HasVisibilityScope` con el scope `visibleTo(User $user)` según el alcance del rol: `own` (propios), `branch` (su sucursal), `all`.
- Los listados siempre usan `visibleTo()`; las Policies lo verifican por recurso. Fuera de alcance → 404.
- Usuarios de portal (viajero, empresa, agencia aliada) usan un guard propio y solo ven expedientes donde son titular, pasajero, empresa cliente o agencia vendedora.
- Catálogos de referencia (países, aeropuertos, monedas, contenido estático de hoteles) no llevan `branch_id`.

## Tests de arquitectura (`tests/Arch/`)

```php
arch('modules do not reach into other modules internals')
    ->expect('App\Modules')
    ->toOnlyUseModulePublicApi(); // expectativa custom en tests/Pest.php, recorre MODULES

arch('actions have a single public execute method')->expect('App\Modules\*\Actions')->toHaveMethod('execute')->toBeFinal();
arch('no floats for money')->expect('App\Modules')->not->toUse(['floatval']);
arch('operational models apply visibility scope')->expect('App\Modules\*\Models')->toUseTrait(HasVisibilityScope::class)->ignoring(REFERENCE_MODELS);
arch('domain does not depend on adapters')->expect('App\Modules')->not->toUse('App\Modules\Integrations\Adapters')->ignoring('App\Modules\Integrations');
arch('no debugging')->expect(['dd', 'dump', 'ray', 'var_dump'])->not->toBeUsed();
```

## Cuándo crear un ADR (`.claude/adr/NNNN-titulo.md`, comando `/adr`)
Cambiar stack, agregar un módulo con dependencias nuevas, romper un límite, cambiar el modelo de alcance y permisos, elegir un proveedor externo, decidir sobre consistencia (saga vs. transacción) o sobre almacenamiento de datos sensibles.

## Checklist de cumplimiento
- [ ] La clase está en el módulo y la carpeta correctos; solo se crearon las carpetas necesarias.
- [ ] Solo se usa la API pública de otros módulos (Contracts, Data, Enums, Events, Models de lectura).
- [ ] Ningún módulo escribe tablas de otro; comunicación por contrato o evento; sin ciclos.
- [ ] Provider registrado, rutas con nombres `<modulo>.*`, traducciones en `lang/es/<modulo>.php`.
- [ ] Rutas nuevas protegidas por su capacidad; la funcionalidad sigue en pie con las demás capacidades apagadas.
- [ ] Modelos operativos con `HasVisibilityScope`; listados con `visibleTo()`.
- [ ] Arch tests actualizados y en verde; ADR si se cambió un límite.
