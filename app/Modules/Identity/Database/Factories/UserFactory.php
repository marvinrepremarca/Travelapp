<?php

declare(strict_types=1);

namespace App\Modules\Identity\Database\Factories;

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Organization\Models\Branch;
use App\Modules\Shared\Enums\VisibilityScope;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<User> */
final class UserFactory extends Factory
{
    private const REMEMBER_TOKEN_LENGTH = 10;

    protected $model = User::class;

    private static ?string $password = null;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => self::$password ??= Hash::make('password'),
            'remember_token' => Str::random(self::REMEMBER_TOKEN_LENGTH),
            'visibility_scope' => VisibilityScope::Own,
            'branch_id' => Branch::factory(),
            'is_active' => true,
        ];
    }

    public function withScope(VisibilityScope $scope): self
    {
        return $this->state(['visibility_scope' => $scope]);
    }

    /** Asigna el rol (debe existir: ejecuta RolesAndPermissionsSeeder) y su alcance por defecto. */
    public function withRole(Role $role): self
    {
        return $this->state(['visibility_scope' => $role->defaultScope()])
            ->afterCreating(static fn(User $user) => $user->assignRole($role->value));
    }

    public function unverified(): self
    {
        return $this->state(['email_verified_at' => null]);
    }
}
