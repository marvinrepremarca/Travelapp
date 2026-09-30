<?php

declare(strict_types=1);

namespace App\Modules\Crm\Livewire;

use App\Modules\Crm\Actions\ReassignCustomerAction;
use App\Modules\Crm\Actions\RecordConsentAction;
use App\Modules\Crm\Actions\RevealCustomerFieldAction;
use App\Modules\Crm\Actions\RevealTravelerPassportAction;
use App\Modules\Crm\Enums\ConsentChannel;
use App\Modules\Crm\Enums\ConsentPurpose;
use App\Modules\Crm\Enums\SensitiveCustomerField;
use App\Modules\Crm\Exceptions\CustomerRuleViolation;
use App\Modules\Crm\Models\Customer;
use App\Modules\Identity\Models\User;
use App\Modules\Shared\Enums\Permission;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.backoffice')]
final class CustomerShow extends Component
{
    #[Locked]
    public string $customerUlid;

    /**
     * Valores revelados en esta vista; no se guardan en ninguna parte.
     *
     * @var array<string, string>
     */
    public array $revealed = [];

    public string $revealReason = '';

    public string $newOwnerId = '';

    public string $consentChannel = 'phone';

    public function mount(Customer $customer): void
    {
        Gate::authorize('view', $customer);
        $this->customerUlid = $customer->ulid;
    }

    public function reveal(string $field, RevealCustomerFieldAction $reveal): void
    {
        $this->validate(['revealReason' => ['required', 'string', 'max:255']], attributes: ['revealReason' => __('crm.customers.reveal_reason')]);

        try {
            $this->revealed[$field] = $reveal->execute($this->customer(), SensitiveCustomerField::from($field), $this->actor(), $this->revealReason);
        } catch (CustomerRuleViolation $violation) {
            $this->addError('revealReason', $violation->getMessage());
        }
    }

    public function revealPassport(string $travelerUlid, RevealTravelerPassportAction $reveal): void
    {
        $this->validate(['revealReason' => ['required', 'string', 'max:255']], attributes: ['revealReason' => __('crm.customers.reveal_reason')]);
        $traveler = $this->customer()->travelers()->where('ulid', $travelerUlid)->first() ?? abort(404);

        try {
            $this->revealed["passport:{$travelerUlid}"] = $reveal->execute($traveler, $this->actor(), $this->revealReason);
        } catch (CustomerRuleViolation $violation) {
            $this->addError('revealReason', $violation->getMessage());
        }
    }

    public function toggleMarketing(RecordConsentAction $record): void
    {
        $customer = $this->customer();
        Gate::authorize('update', $customer);

        $record->execute($customer, ConsentPurpose::Marketing, ! $customer->hasConsent(ConsentPurpose::Marketing), ConsentChannel::from($this->consentChannel), $this->actor());
    }

    public function reassign(ReassignCustomerAction $reassign): void
    {
        $customer = $this->customer();
        Gate::authorize('reassign', $customer);

        try {
            $reassign->execute($customer, (int) $this->newOwnerId, $this->actor());
        } catch (CustomerRuleViolation $violation) {
            $this->addError('newOwnerId', $violation->getMessage());

            return;
        }

        session()->flash('status', __('crm.customers.reassigned'));
        $this->redirectRoute('crm.customers.show', $customer, navigate: true);
    }

    public function render(): View
    {
        $customer = $this->customer();
        $customer->load(['consents', 'travelers']);
        $actor = $this->actor();

        return view('crm::livewire.customer-show', [
            'customer' => $customer,
            'owner' => User::query()->find($customer->owner_id),
            'canReveal' => $actor->can(Permission::SensitiveDataView->value),
            'canReassign' => Gate::allows('reassign', $customer),
            'assignableOwners' => User::query()->visibleTo($actor)->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all(),
            'marketing' => $customer->hasConsent(ConsentPurpose::Marketing),
            'channels' => ConsentChannel::cases(),
            'fields' => SensitiveCustomerField::cases(),
            'today' => CarbonImmutable::today(),
            'passportWarningMonths' => config()->integer('travel.crm.passport_min_validity_months'),
        ])->title($customer->display_name)
            ->layoutData(['heading' => $customer->display_name]);
    }

    private function customer(): Customer
    {
        return Customer::query()->visibleTo($this->actor())->where('ulid', $this->customerUlid)->first() ?? abort(404);
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
