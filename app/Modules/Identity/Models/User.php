<?php

declare(strict_types=1);

namespace App\Modules\Identity\Models;

use App\Modules\Identity\Database\Factories\UserFactory;
use App\Modules\Organization\Models\Branch;
use App\Modules\Shared\Contracts\ScopedViewer;
use App\Modules\Shared\Enums\VisibilityScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
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
 */
final class User extends Authenticatable implements ScopedViewer
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use HasRoles;
    use HasUlids;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /** `visibility_scope`, `branch_id` y roles nunca vienen del request: se asignan en Actions. */
    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

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
