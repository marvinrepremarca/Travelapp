<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Data\UserData;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Notifications\UserInvitation;
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
        private CreateUserAction $create,
    ) {}

    public function execute(UserData $data, User $actor): User
    {
        $user = $this->create->execute($data, $actor, Str::password(self::RANDOM_PASSWORD_LENGTH), false);

        $user->notify(new UserInvitation(Password::createToken($user), $actor->name));

        return $user;
    }
}
