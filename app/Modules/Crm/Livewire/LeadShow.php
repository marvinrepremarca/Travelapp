<?php

declare(strict_types=1);

namespace App\Modules\Crm\Livewire;

use App\Modules\Crm\Actions\LogLeadInteractionAction;
use App\Modules\Crm\Actions\MoveLeadAction;
use App\Modules\Crm\Enums\InteractionType;
use App\Modules\Crm\Enums\LeadStatus;
use App\Modules\Crm\Enums\LostReason;
use App\Modules\Crm\Exceptions\LeadRuleViolation;
use App\Modules\Crm\Models\Customer;
use App\Modules\Crm\Models\Lead;
use App\Modules\Identity\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.backoffice')]
final class LeadShow extends Component
{
    #[Locked]
    public string $leadUlid;

    public string $interactionType = 'call';

    public string $interactionSummary = '';

    public string $lostReason = '';

    public string $lostNote = '';

    public string $customerUlid = '';

    public function mount(Lead $lead): void
    {
        Gate::authorize('update', $lead);
        $this->leadUlid = $lead->ulid;
    }

    public function logInteraction(LogLeadInteractionAction $log): void
    {
        $validated = $this->validate([
            'interactionType' => ['required', Rule::enum(InteractionType::class)],
            'interactionSummary' => ['required', 'string', 'max:2000'],
        ], attributes: ['interactionType' => __('crm.leads.fields.interaction_type'), 'interactionSummary' => __('crm.leads.fields.interaction_summary')]);

        $log->execute($this->lead(), InteractionType::from($validated['interactionType']), $validated['interactionSummary'], $this->actor());
        $this->reset('interactionSummary');
    }

    public function move(string $status, MoveLeadAction $move): void
    {
        $this->resetErrorBag();
        $next = LeadStatus::from($status);
        $customer = $next === LeadStatus::Won && $this->customerUlid !== ''
            ? Customer::query()->visibleTo($this->actor())->where('ulid', $this->customerUlid)->first()
            : null;

        try {
            $move->execute($this->lead(), $next, $this->actor(), LostReason::tryFrom($this->lostReason), $this->lostNote ?: null, $customer);
        } catch (LeadRuleViolation $violation) {
            $this->addError($next === LeadStatus::Won ? 'customerUlid' : ($next === LeadStatus::Lost ? 'lostReason' : 'status'), $violation->getMessage());
        }
    }

    public function render(): View
    {
        $lead = $this->lead()->load(['interactions', 'customer']);

        return view('crm::livewire.lead-show', [
            'lead' => $lead,
            'interactionTypes' => InteractionType::cases(),
            'lostReasons' => LostReason::cases(),
            'customers' => Customer::query()->visibleTo($this->actor())->orderBy('display_name')->limit(config()->integer('travel.crm.per_page'))->pluck('display_name', 'ulid')->all(),
            'users' => User::query()->whereIn('id', $lead->interactions->pluck('user_id')->push($lead->owner_id))->pluck('name', 'id'),
            'nextSteps' => array_values(array_filter($lead->status->allowedTransitions(), static fn(LeadStatus $status): bool => ! in_array($status, [LeadStatus::Won, LeadStatus::Lost], true))),
            'canWin' => $lead->status->canTransitionTo(LeadStatus::Won),
            'canLose' => $lead->status->canTransitionTo(LeadStatus::Lost),
        ])->title($lead->contact_name)
            ->layoutData(['heading' => $lead->contact_name]);
    }

    private function lead(): Lead
    {
        return Lead::query()->visibleTo($this->actor())->where('ulid', $this->leadUlid)->first() ?? abort(404);
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
