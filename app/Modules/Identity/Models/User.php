<?php

declare(strict_types=1);

namespace App\Modules\Identity\Models;

use App\Modules\Identity\Database\Factories\UserFactory;
use App\Modules\Identity\Enums\Role;
use App\Modules\Organization\Models\Branch;
use App\Modules\Shared\Concerns\HasVisibilityScope;
use App\Modules\Shared\Contracts\ScopedViewer;
use App\Modules\Shared\Enums\AuditLogName;
use App\Modules\Shared\Enums\VisibilityScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

/**
 * Usuario interno de la agencia.
 *
 * @property int $id
 * @property string $ulid
 * @property string $name
 * @property string $email
 * @property CarbonImmutable|null $email_verified_at
 * @property VisibilityScope $visibility_scope
 * @property int|null $branch_id
 * @property bool $is_active
 * @property CarbonImmutable|null $two_factor_confirmed_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 */
final class User extends Authenticatable implements ScopedViewer
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use HasRoles;
    use HasUlids;
    use HasVisibilityScope;
    use LogsActivity;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /** `visibility_scope`, `branch_id` y roles nunca vienen del request: se asignan en Actions. */
    protected $fillable = ['name', 'email', 'password'];

    /** Mismos valores por defecto que la BD: una instancia nueva se lee igual que una cargada, sin recargarla. */
    protected $attributes = [
        'two_factor_secret' => null,
        'two_factor_recovery_codes' => null,
        'two_factor_confirmed_at' => null,
    ];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function hasTwoFactorEnabled(): bool
    {
        // Recién creado por código, el atributo puede no estar cargado: equivale a no confirmado.
        return ($this->attributes['two_factor_confirmed_at'] ?? null) !== null;
    }

    /** Algún rol del usuario exige segundo factor ⚙. */
    public function requiresTwoFactor(): bool
    {
        foreach ($this->getRoleNames() as $roleName) {
            if (Role::tryFrom($roleName)?->requiresTwoFactor() === true) {
                return true;
            }
        }

        return false;
    }

    public function mustEnableTwoFactor(): bool
    {
        return $this->requiresTwoFactor() && ! $this->hasTwoFactorEnabled();
    }

    /** Nunca se registran contraseñas ni secretos de 2FA. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'visibility_scope', 'branch_id', 'is_active'])
            ->logOnlyDirty()
            ->useLogName(AuditLogName::Identity->value);
    }

    /** Alcance "propio" sobre usuarios = solo uno mismo. */
    protected function ownerColumn(): string
    {
        return 'id';
    }

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function visibilityScope(): VisibilityScope
    {
        return $this->visibility_scope;
    }

    public function viewerId(): int
    {
        return $this->id;
    }

    public function viewerBranchId(): ?int
    {
        return $this->branch_id;
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'immutable_datetime',
            'two_factor_confirmed_at' => 'immutable_datetime',
            'password' => 'hashed',
            'visibility_scope' => VisibilityScope::class,
            'is_active' => 'boolean',
        ];
    }
}
