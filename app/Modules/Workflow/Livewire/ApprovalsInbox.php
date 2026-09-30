<?php

declare(strict_types=1);

namespace App\Modules\Workflow\Livewire;

use App\Modules\Identity\Models\User;
use App\Modules\Organization\Contracts\AppSettings;
use App\Modules\Workflow\Actions\CancelApprovalAction;
use App\Modules\Workflow\Actions\ResolveApprovalAction;
use App\Modules\Workflow\Enums\ApprovalStatus;
use App\Modules\Workflow\Enums\ApprovalType;
use App\Modules\Workflow\Exceptions\InvalidApproval;
use App\Modules\Workflow\Models\ApprovalRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Bandeja de aprobaciones: lo que me toca decidir (según permisos y alcance) y lo que yo pedí. */
#[Layout('components.layouts.backoffice')]
final class ApprovalsInbox extends Component
{
    /** @var array<string, string> Nota de decisión por ULID de solicitud */
    public array $notes = [];

    public function approve(string $ulid, ResolveApprovalAction $resolve): void
    {
        $this->resolve($ulid, ApprovalStatus::Approved, $resolve);
    }

    public function reject(string $ulid, ResolveApprovalAction $resolve): void
    {
        $this->resolve($ulid, ApprovalStatus::Rejected, $resolve);
    }

    public function cancel(string $ulid, CancelApprovalAction $cancel): void
    {
        $approval = ApprovalRequest::query()->where('ulid', $ulid)->where('owner_id', $this->actor()->id)->first() ?? abort(404);

        $this->resetErrorBag("notes.{$ulid}");

        try {
            $cancel->execute($approval, $this->actor());
        } catch (InvalidApproval $exception) {
            $this->addError("notes.{$ulid}", $exception->getMessage());
        }
    }

    public function render(AppSettings $settings): View
    {
        $pendingForMe = ApprovalRequest::query()
            ->visibleTo($this->actor())
            ->where('status', ApprovalStatus::Pending)
            ->where('owner_id', '!=', $this->actor()->id)
            ->whereIn('type', $this->decidableTypes())
            ->oldest()
            ->limit(config()->integer('travel.workflow.per_page'))
            ->get();

        $myRequests = ApprovalRequest::query()
            ->where('owner_id', $this->actor()->id)
            ->latest('id')
            ->limit(config()->integer('travel.workflow.per_page'))
            ->get();

        $requesters = User::query()->whereIn('id', $pendingForMe->pluck('owner_id'))->pluck('name', 'id');

        return view('workflow::livewire.approvals-inbox', [
            'pendingForMe' => $pendingForMe,
            'myRequests' => $myRequests,
            'requesters' => $requesters,
            'timezone' => $settings->agencyTimezone(),
        ])->title(__('workflow.approvals.title'))
            ->layoutData(['heading' => __('workflow.approvals.title')]);
    }

    private function resolve(string $ulid, ApprovalStatus $decision, ResolveApprovalAction $resolve): void
    {
        $approval = ApprovalRequest::query()->visibleTo($this->actor())->where('ulid', $ulid)->first() ?? abort(404);

        $this->resetErrorBag("notes.{$ulid}");

        try {
            $resolve->execute($approval, $decision, $this->actor(), $this->notes[$ulid] ?? null);
        } catch (InvalidApproval $exception) {
            $this->addError("notes.{$ulid}", $exception->getMessage());

            return;
        }

        unset($this->notes[$ulid]);
        session()->flash('status', __($decision === ApprovalStatus::Approved ? 'workflow.approvals.approved' : 'workflow.approvals.rejected'));
    }

    /** @return list<string> */
    private function decidableTypes(): array
    {
        return array_values(array_map(
            static fn(ApprovalType $type): string => $type->value,
            array_filter(ApprovalType::cases(), fn(ApprovalType $type): bool => $this->actor()->can($type->approverPermission()->value)),
        ));
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
