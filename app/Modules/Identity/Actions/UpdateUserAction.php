<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Data\UserData;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\UserChangeGuard;
use Illuminate\Support\Facades\DB;

final readonly class UpdateUserAction
{
    public function __construct(private UserChangeGuard $guard) {}

    public function execute(User $user, UserData $data, User $actor): User
    {
        $this->guard->check($data, $actor, $user);

        return DB::transaction(function () use ($user, $data): User {
            $user->fill(['name' => $data->name, 'email' => mb_strtolower($data->email)]);
            $user->visibility_scope = $data->scope;
            $user->branch_id = $data->branchId;
            $user->save();
            $user->syncRoles([$data->role->value]);

            return $user;
        });
    }
}
