---
name: laravel-backend
description: Patrones de código backend Laravel 12 del proyecto con ejemplos. Úsala al escribir controllers, componentes Livewire con lógica, FormRequests, DTOs, Actions, enums con máquina de estados, modelos Eloquent, Policies, excepciones de negocio, value objects, sagas o la configuración base de la app.
---

# Backend Laravel: plantillas

## Controller delgado

```php
final class BookingItemCancellationController
{
    public function store(CancelBookingItemRequest $request, BookingItem $item, CancelBookingItemAction $cancel): RedirectResponse
    {
        $result = $cancel->execute($item, $request->toData(), $request->user());

        return to_route('bookings.show', $item->booking)
            ->with('status', __('bookings.item_cancelled', ['penalty' => $result->penalty->formatTo(app()->getLocale())]));
    }
}
```

## FormRequest → DTO

```php
final class CancelBookingItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('cancel', $this->route('item'));
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'reason' => ['required', Rule::enum(CancellationReason::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'accept_penalty' => ['accepted'],
        ];
    }

    public function toData(): CancellationData
    {
        return new CancellationData(
            reason: $this->enum('reason', CancellationReason::class),
            notes: $this->string('notes')->toString() ?: null,
        );
    }
}
```

## Action (un caso de uso)

```php
final readonly class CancelBookingItemAction
{
    public function __construct(
        private CancellationPenaltyCalculator $penalties,
        private ProviderRegistry $providers,
    ) {}

    public function execute(BookingItem $item, CancellationData $data, User $actor): CancellationResult
    {
        if (! $item->status->canTransitionTo(BookingItemStatus::Cancelled)) {
            throw InvalidBookingItemTransition::from($item->status, BookingItemStatus::Cancelled);
        }

        $quote = $this->penalties->for($item, CarbonImmutable::now());

        // Llamada externa FUERA de la transacción; idempotente por clave.
        $confirmation = $this->providers->for($item)->cancel($item->toProviderReference(), $item->idempotencyKey('cancel'));

        return DB::transaction(function () use ($item, $data, $actor, $quote, $confirmation): CancellationResult {
            $item->markCancelled($quote, $confirmation, $data, $actor);
            event(new BookingItemCancelled($item->ulid, $quote->penalty));

            return new CancellationResult($quote->penalty, $quote->refundable);
        });
    }
}
```

## Enum con máquina de estados

```php
enum BookingItemStatus: string
{
    case Held = 'held';
    case OnRequest = 'on_request';
    case Confirmed = 'confirmed';
    case Ticketed = 'ticketed';
    case Cancelled = 'cancelled';
    // …

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Held => [self::Confirmed, self::Ticketed, self::Cancelled, self::Expired],
            self::OnRequest => [self::Confirmed, self::Rejected, self::Cancelled],
            self::Confirmed => [self::Ticketed, self::Vouchered, self::Cancelled, self::Changed],
            // …
            default => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    public function label(): string { return __("bookings.item_status.{$this->value}"); }
    public function tone(): Tone { return match ($this) { self::Cancelled => Tone::Danger, self::Held => Tone::Warning, default => Tone::Neutral }; }
}
```

## Modelo

```php
final class BookingItem extends Model
{
    use HasFactory, HasVisibilityScope, HasUlids, SoftDeletes;

    protected $fillable = ['product_type', 'supplier_id', 'service_start_date', 'service_end_date'];

    protected function casts(): array
    {
        return [
            'status' => BookingItemStatus::class,
            'product_type' => ProductType::class,
            'service_start_date' => 'immutable_date',
            'sale_amount' => MoneyCast::class.':sale_amount_minor,sale_currency',
            'cancellation_policy' => CancellationPolicyCast::class, // snapshot inmutable
        ];
    }

    public function uniqueIds(): array { return ['ulid']; }
    public function getRouteKeyName(): string { return 'ulid'; }

    /** @return BelongsTo<Booking, $this> */
    public function booking(): BelongsTo { return $this->belongsTo(Booking::class); }
}
```

## Policy

```php
final class BookingItemPolicy
{
    public function cancel(User $user, BookingItem $item): bool
    {
        return $user->can(Permission::BookingsCancel->value)
            && $item->booking->isVisibleTo($user); // own | branch | agency
    }
}
```

## Excepciones de negocio

`final class FareNoLongerAvailable extends BusinessRuleException` con constructor nombrado (`::forItem($item)`), mensaje con `__()` y código estable (`fare_no_longer_available`) para la API. El handler las convierte en 422 (API) o mensaje flash (web).

## Configuración base (`AppServiceProvider::boot`)

```php
Model::shouldBeStrict(! app()->isProduction());   // lazy loading, atributos inexistentes, fillable silencioso
Model::unguard(false);
Date::use(CarbonImmutable::class);
DB::prohibitDestructiveCommands(app()->isProduction());
URL::forceHttps(app()->isProduction());
Vite::useAggressivePrefetching();
Password::defaults(fn () => Password::min(12)->mixedCase()->numbers()->uncompromised());
RateLimiter::for('search', fn (Request $r) => Limit::perMinute(30)->by($r->user()?->id ?: $r->ip()));
```

## Parámetros de negocio
Defaults en `config/travel.php`; editables desde la administración en la tabla `settings` vía `AppSettings` (contrato de `Organization`) con caché. Nunca constantes quemadas.

## Checklist de cumplimiento
- [ ] `declare(strict_types=1)`, clases `final`, tipado completo, sin `mixed` injustificado.
- [ ] Controller/Livewire delgado → FormRequest → DTO → Action con un único `execute()`.
- [ ] Autorización con Policy/`Gate::authorize()`.
- [ ] Estados como enum con `canTransitionTo()`; excepciones de negocio con mensaje traducible.
- [ ] `DB::transaction()` para escrituras múltiples, sin llamadas HTTP dentro.
- [ ] Sin N+1, `$fillable` explícito, `CarbonImmutable`, `Money`.
- [ ] Sin strings ni números mágicos: enums, config, settings, constantes con nombre, `__()`.
