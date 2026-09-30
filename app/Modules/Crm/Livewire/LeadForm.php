<?php

declare(strict_types=1);

namespace App\Modules\Crm\Livewire;

use App\Modules\Crm\Actions\SaveLeadAction;
use App\Modules\Crm\Data\LeadData;
use App\Modules\Crm\Exceptions\LeadRuleViolation;
use App\Modules\Crm\Models\Lead;
use App\Modules\Identity\Models\User;
use App\Modules\Shared\Enums\SalesChannel;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.backoffice')]
final class LeadForm extends Component
{
    #[Locked]
    public ?string $leadUlid = null;

    public string $contact_name = '';

    public string $email = '';

    public string $phone = '';

    public string $channel = 'whatsapp';

    public string $destination = '';

    public string $travel_start = '';

    public string $travel_end = '';

    public string $travelers_count = '';

    public string $notes = '';

    public function mount(?Lead $lead = null): void
    {
        if (! $lead?->exists) {
            return;
        }

        Gate::authorize('update', $lead);
        $this->leadUlid = $lead->ulid;
        $this->fill([
            'contact_name' => $lead->contact_name,
            'email' => (string) $lead->email,
            'phone' => (string) $lead->phone,
            'channel' => $lead->channel->value,
            'destination' => (string) $lead->destination,
            'travel_start' => (string) $lead->travel_start?->toDateString(),
            'travel_end' => (string) $lead->travel_end?->toDateString(),
            'travelers_count' => (string) $lead->travelers_count,
            'notes' => (string) $lead->notes,
        ]);
    }

    public function save(SaveLeadAction $save): void
    {
        $lead = $this->leadUlid === null ? null : (Lead::query()->visibleTo($this->actor())->where('ulid', $this->leadUlid)->first() ?? abort(404));

        $validated = $this->validate([
            'contact_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'channel' => ['required', Rule::enum(SalesChannel::class)],
            'destination' => ['nullable', 'string', 'max:255'],
            'travel_start' => ['nullable', 'date'],
            'travel_end' => ['nullable', 'date', 'after_or_equal:travel_start'],
            'travelers_count' => ['nullable', 'integer', 'min:1', 'max:' . config()->integer('travel.crm.max_lead_travelers')],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], attributes: $this->attributes());

        try {
            $saved = $save->execute(new LeadData(
                contactName: $validated['contact_name'],
                channel: SalesChannel::from($validated['channel']),
                email: $validated['email'] ?: null,
                phone: $validated['phone'] ?: null,
                destination: $validated['destination'] ?: null,
                travelStart: $validated['travel_start'] ? CarbonImmutable::parse($validated['travel_start']) : null,
                travelEnd: $validated['travel_end'] ? CarbonImmutable::parse($validated['travel_end']) : null,
                travelersCount: $validated['travelers_count'] !== '' && $validated['travelers_count'] !== null ? (int) $validated['travelers_count'] : null,
                notes: $validated['notes'] ?: null,
            ), $this->actor(), $lead);
        } catch (LeadRuleViolation $violation) {
            $this->addError('email', $violation->getMessage());

            return;
        }

        session()->flash('status', __('crm.leads.saved'));
        $this->redirectRoute('crm.leads.show', $saved, navigate: true);
    }

    public function render(): View
    {
        $title = $this->leadUlid === null ? __('crm.leads.create') : __('crm.leads.edit');

        return view('crm::livewire.lead-form', [
            'channels' => SalesChannel::cases(),
        ])->title($title)->layoutData(['heading' => $title]);
    }

    /** @return array<string, string> */
    private function attributes(): array
    {
        /** @var array<string, string> */
        return trans('crm.leads.fields');
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
