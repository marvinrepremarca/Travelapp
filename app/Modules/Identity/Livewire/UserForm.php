<?php

declare(strict_types=1);

namespace App\Modules\Identity\Livewire;

use App\Modules\Identity\Actions\InviteUserAction;
use App\Modules\Identity\Actions\UpdateUserAction;
use App\Modules\Identity\Data\UserData;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Exceptions\UserManagementViolation;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\AssignableRoles;
use App\Modules\Organization\Models\Branch;
use App\Modules\Shared\Enums\VisibilityScope;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.backoffice')]
final class UserForm extends Component
{
    #[Locked]
    public ?string $userUlid = null;

    public string $name = '';

    public string $email = '';

    public string $role = '';

    public string $branch_id = '';

    public string $visibility_scope = '';

    public function mount(?User $user = null): void
    {
        if ($user?->exists) {
            $visible = User::query()->visibleTo($this->actor())->whereKey($user->id)->first();
            abort_if($visible === null, 404);
            Gate::authorize('update', $visible);

            $this->userUlid = $visible->ulid;
            $this->fill([
                'name' => $visible->name,
                'email' => $visible->email,
                'role' => (string) $visible->getRoleNames()->first(),
                'branch_id' => (string) $visible->branch_id,
                'visibility_scope' => $visible->visibility_scope->value,
            ]);

            return;
        }

        Gate::authorize('create', User::class);
        $this->branch_id = (string) $this->actor()->branch_id;
    }

    /** Al elegir un rol se propone su alcance por defecto; el administrador puede cambiarlo. */
    public function updatedRole(string $value): void
    {
        $role = Role::tryFrom($value);

        if ($role !== null) {
            $this->visibility_scope = $role->defaultScope()->value;
        }
    }

    public function save(InviteUserAction $invite, UpdateUserAction $update): void
    {
        $target = $this->target();
        Gate::authorize($target instanceof User ? 'update' : 'create', $target ?? User::class);

        $validated = $this->validate();
        $data = new UserData(
            name: $validated['name'],
            email: $validated['email'],
            role: Role::from($validated['role']),
            branchId: $validated['branch_id'] === '' || $validated['branch_id'] === null ? null : (int) $validated['branch_id'],
            scope: VisibilityScope::from($validated['visibility_scope']),
        );

        try {
            $saved = $target instanceof User
                ? $update->execute($target, $data, $this->actor())
                : $invite->execute($data, $this->actor());
        } catch (UserManagementViolation $violation) {
            $this->addError($violation->errorCode() === 'branch_required' ? 'branch_id' : 'role', $violation->getMessage());

            return;
        }

        session()->flash('status', __($target instanceof User ? 'identity.users.saved' : 'identity.users.invited', ['name' => $saved->name, 'email' => $saved->email]));
        $this->redirectRoute('identity.users.index', navigate: true);
    }

    public function render(AssignableRoles $assignable): View
    {
        $title = $this->userUlid === null ? __('identity.users.create') : __('identity.users.edit');

        return view('identity::livewire.user-form', [
            'roles' => collect($assignable->for($this->actor()))->mapWithKeys(fn(Role $role): array => [$role->value => $role->label()])->all(),
            'branches' => Branch::query()->active()->orderBy('name')->pluck('name', 'id')->all(),
            'scopes' => collect(VisibilityScope::cases())->mapWithKeys(fn(VisibilityScope $scope): array => [$scope->value => $scope->label()])->all(),
            'isEditing' => $this->userUlid !== null,
        ])->title($title)->layoutData(['heading' => $title]);
    }

    /** @return array<string, list<mixed>> */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->userUlid, 'ulid')],
            'role' => ['required', Rule::enum(Role::class)],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')->where('is_active', true)],
            'visibility_scope' => ['required', Rule::enum(VisibilityScope::class)],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        /** @var array<string, string> */
        return trans('identity.users.fields');
    }

    private function target(): ?User
    {
        return $this->userUlid === null
            ? null
            : User::query()->visibleTo($this->actor())->where('ulid', $this->userUlid)->firstOrFail();
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
