<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Exceptions\UserManagementViolation;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Activa o desactiva un usuario. Al desactivarlo se cierran todas sus sesiones. */
final class SetUserActiveAction
{
    private const REMEMBER_TOKEN_LENGTH = 60;

    public function execute(User $user, bool $active, User $actor): User
    {
        if (! $active && $user->is($actor)) {
            throw UserManagementViolation::cannotDeactivateSelf();
        }

        DB::transaction(function () use ($user, $active): void {
            $user->is_active = $active;

            if (! $active) {
                // Rotar el token invalida las cookies "recordarme" ya emitidas.
                $user->setRememberToken(Str::random(self::REMEMBER_TOKEN_LENGTH));
                DB::table(config()->string('session.table'))->where('user_id', $user->id)->delete();
            }

            $user->save();
        });

        return $user;
    }
}
