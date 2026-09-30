<?php

declare(strict_types=1);

namespace App\Modules\Identity\Livewire;

use App\Modules\Identity\Actions\ResendInvitationAction;
use App\Modules\Identity\Actions\SetUserActiveAction;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Exceptions\UserManagementViolation;
use App\Modules\Identity\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.backoffice')]
final class UsersIndex extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $role = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRole(): void
    {
        $this->resetPage();
    }

    public function toggleActive(string $ulid, SetUserActiveAction $setActive): void
    {
        $target = $this->findVisible($ulid);
        Gate::authorize('update', $target);

        try {
            $setActive->execute($target, ! $target->is_active, $this->actor());
        } catch (UserManagementViolation $violation) {
            $this->addError('actions', $violation->getMessage());

            return;
        }

        session()->flash('status', __($target->is_active ? 'identity.users.activated' : 'identity.users.deactivated', ['name' => $target->name]));
    }

    public function resendInvitation(string $ulid, ResendInvitationAction $resend): void
    {
        $target = $this->findVisible($ulid);
        Gate::authorize('update', $target);

        $resend->execute($target, $this->actor());
        session()->flash('status', __('identity.users.invitation_resent', ['email' => $target->email]));
    }

    public function render(): View
    {
        $users = User::query()
            ->visibleTo($this->actor())
            ->with(['roles', 'branch'])
            ->when($this->search !== '', fn(Builder $query) => $query->where(fn(Builder $inner) => $inner
                ->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('email', 'like', '%' . $this->search . '%')))
            ->when(Role::tryFrom($this->role) !== null, fn(Builder $query) => $query->role($this->role))
            ->orderBy('name')
            ->paginate(config()->integer('travel.identity.users_per_page'));

        return view('identity::livewire.users-index', [
            'users' => $users,
            'roles' => Role::cases(),
            'canManage' => Gate::allows('create', User::class),
        ])->title(__('identity.users.title'))
            ->layoutData(['heading' => __('identity.users.title')]);
    }

    private function findVisible(string $ulid): User
    {
        return User::query()->visibleTo($this->actor())->where('ulid', $ulid)->first() ?? abort(404);
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
