<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Data\UserData;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Notifications\UserInvitation;
use App\Modules\Identity\Services\UserChangeGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Crea el usuario con una contraseña aleatoria que nadie conoce y le envía
 * un enlace para definir la suya. El administrador nunca maneja contraseñas.
 */
final readonly class InviteUserAction
{
    private const RANDOM_PASSWORD_LENGTH = 64;

    public function __construct(
        private UserChangeGuard $guard,
    ) {}

    public function execute(UserData $data, User $actor): User
    {
        $this->guard->check($data, $actor);

        $user = DB::transaction(function () use ($data): User {
            $user = new User(['name' => $data->name, 'email' => mb_strtolower($data->email), 'password' => Str::password(self::RANDOM_PASSWORD_LENGTH)]);
            $user->visibility_scope = $data->scope;
            $user->branch_id = $data->branchId;
            $user->is_active = true;
            $user->save();
            $user->syncRoles([$data->role->value]);

            return $user;
        });

        $user->notify(new UserInvitation(Password::createToken($user), $actor->name));

        return $user;
    }
}
