<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Data\UserData;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\UserChangeGuard;
use Illuminate\Support\Facades\DB;

/** Crea el usuario con su rol, alcance y sucursal. Quien llama decide la contraseña inicial. */
final readonly class CreateUserAction
{
    public function __construct(
        private UserChangeGuard $guard,
    ) {}

    public function execute(UserData $data, User $actor, string $password, bool $mustChangePassword): User
    {
        $this->guard->check($data, $actor);

        return DB::transaction(function () use ($data, $password, $mustChangePassword): User {
            $user = new User(['name' => $data->name, 'email' => mb_strtolower($data->email), 'password' => $password]);
            $user->visibility_scope = $data->scope;
            $user->branch_id = $data->branchId;
            $user->is_active = true;
            $user->must_change_password = $mustChangePassword;
            $user->save();
            $user->syncRoles([$data->role->value]);

            return $user;
        });
    }
}
