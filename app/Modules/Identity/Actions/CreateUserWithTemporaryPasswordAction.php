<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Data\UserData;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\TemporaryPasswordGenerator;
use App\Modules\Shared\Enums\AuditLogName;

/**
 * Alta sin correo: el sistema genera una contraseña temporal que el administrador entrega en persona.
 * El usuario debe cambiarla en su primer ingreso. Devuelve la contraseña en claro solo para mostrarla una vez.
 */
final readonly class CreateUserWithTemporaryPasswordAction
{
    public function __construct(
        private CreateUserAction $create,
        private TemporaryPasswordGenerator $passwords,
    ) {}

    /** @return array{user: User, password: string} */
    public function execute(UserData $data, User $actor): array
    {
        $password = $this->passwords->generate();
        $user = $this->create->execute($data, $actor, $password, true);

        // Cifrada en reposo: el gerente la consulta hasta que el usuario define la suya.
        $user->forceFill(['temporary_password' => $password])->save();

        activity()->performedOn($user)->causedBy($actor)->useLog(AuditLogName::Identity->value)->event('temporary_password_issued')->log(__('identity.users.audit_temporary_issued'));

        return ['user' => $user, 'password' => $password];
    }
}
