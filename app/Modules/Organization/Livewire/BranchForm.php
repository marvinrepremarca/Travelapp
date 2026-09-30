<?php

declare(strict_types=1);

namespace App\Modules\Organization\Livewire;

use App\Modules\Organization\Actions\SaveBranchAction;
use App\Modules\Organization\Contracts\AppSettings;
use App\Modules\Organization\Contracts\BranchManagerDirectory;
use App\Modules\Organization\Data\BranchData;
use App\Modules\Organization\Exceptions\InvalidBranchManager;
use App\Modules\Organization\Models\Branch;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.backoffice')]
final class BranchForm extends Component
{
    #[Locked]
    public ?string $branchUlid = null;

    public string $code = '';

    public string $name = '';

    public string $city = '';

    public string $address = '';

    public string $phone = '';

    public string $email = '';

    public string $timezone = '';

    public string $manager_id = '';

    public function mount(AppSettings $settings, ?Branch $branch = null): void
    {
        if ($branch?->exists) {
            Gate::authorize('update', $branch);
            $this->branchUlid = $branch->ulid;
            $this->fill([
                'code' => $branch->code,
                'name' => $branch->name,
                'city' => (string) $branch->city,
                'address' => (string) $branch->address,
                'phone' => (string) $branch->phone,
                'email' => (string) $branch->email,
                'timezone' => $branch->timezone,
                'manager_id' => (string) $branch->manager_id,
            ]);

            return;
        }

        Gate::authorize('create', Branch::class);
        $this->timezone = $settings->agencyTimezone();
    }

    public function save(SaveBranchAction $save): void
    {
        $branch = $this->branch();
        Gate::authorize($branch instanceof Branch ? 'update' : 'create', $branch ?? Branch::class);

        $validated = $this->validate();

        try {
            $saved = $save->execute(new BranchData(
                code: $validated['code'],
                name: $validated['name'],
                city: $validated['city'] ?: null,
                address: $validated['address'] ?: null,
                phone: $validated['phone'] ?: null,
                email: $validated['email'] ?: null,
                timezone: $validated['timezone'],
                managerId: $validated['manager_id'] === '' || $validated['manager_id'] === null ? null : (int) $validated['manager_id'],
            ), $branch);
        } catch (InvalidBranchManager $exception) {
            $this->addError('manager_id', $exception->getMessage());

            return;
        }

        session()->flash('status', __('organization.branches.saved', ['name' => $saved->name]));
        $this->redirectRoute('organization.branches.index', navigate: true);
    }

    public function render(BranchManagerDirectory $managers): View
    {
        $title = $this->branchUlid === null ? __('organization.branches.create') : __('organization.branches.edit');

        return view('organization::livewire.branch-form', [
            'managers' => $managers->candidates(),
            'timezones' => timezone_identifiers_list(),
        ])->title($title)->layoutData(['heading' => $title]);
    }

    /** @return array<string, list<mixed>> */
    protected function rules(): array
    {
        return [
            'code' => ['required', 'alpha_dash', 'max:20', Rule::unique('branches', 'code')->ignore($this->branchUlid, 'ulid')],
            'name' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'timezone' => ['required', 'timezone:all'],
            'manager_id' => ['nullable', 'integer'],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        /** @var array<string, string> */
        return trans('organization.branches.fields');
    }

    private function branch(): ?Branch
    {
        return $this->branchUlid === null ? null : Branch::query()->where('ulid', $this->branchUlid)->firstOrFail();
    }
}
