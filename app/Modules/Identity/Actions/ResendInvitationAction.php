<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Models\User;
use App\Modules\Identity\Notifications\UserInvitation;
use Illuminate\Support\Facades\Password;

final class ResendInvitationAction
{
    public function execute(User $user, User $actor): void
    {
        $user->notify(new UserInvitation(Password::createToken($user), $actor->name));
    }
}
