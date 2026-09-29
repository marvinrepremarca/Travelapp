<?php

declare(strict_types=1);

namespace App\Modules\Organization\Livewire;

use App\Modules\Organization\Actions\SetBranchActiveAction;
use App\Modules\Organization\Contracts\BranchManagerDirectory;
use App\Modules\Organization\Enums\BranchStatusFilter;
use App\Modules\Organization\Models\Branch;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.backoffice')]
final class BranchesIndex extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $status = 'all';

    public function mount(): void
    {
        Gate::authorize('viewAny', Branch::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function toggleActive(string $ulid, SetBranchActiveAction $setActive): void
    {
        $branch = Branch::query()->where('ulid', $ulid)->firstOrFail();
        Gate::authorize('update', $branch);

        $setActive->execute($branch, ! $branch->is_active);

        session()->flash('status', __($branch->is_active ? 'organization.branches.activated' : 'organization.branches.deactivated', ['name' => $branch->name]));
    }

    public function render(BranchManagerDirectory $managers): View
    {
        $filter = BranchStatusFilter::tryFrom($this->status) ?? BranchStatusFilter::All;

        $branches = Branch::query()
            ->when($this->search !== '', fn($query) => $query->where(fn($inner) => $inner
                ->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('code', 'like', '%' . $this->search . '%')
                ->orWhere('city', 'like', '%' . $this->search . '%')))
            ->when($filter !== BranchStatusFilter::All, fn($query) => $query->where('is_active', $filter === BranchStatusFilter::Active))
            ->orderBy('name')
            ->paginate(config()->integer('travel.organization.branches_per_page'));

        $managerIds = array_values(array_filter($branches->getCollection()->pluck('manager_id')->all()));

        return view('organization::livewire.branches-index', [
            'branches' => $branches,
            'managerNames' => $managers->namesOf($managerIds),
            'filters' => BranchStatusFilter::cases(),
        ])->title(__('organization.branches.title'))
            ->layoutData(['heading' => __('organization.branches.title')]);
    }
}
