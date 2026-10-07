<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Livewire;

use App\Modules\Compliance\Actions\RegisterDataRequestAction;
use App\Modules\Compliance\Data\DataRequestData;
use App\Modules\Compliance\Enums\DataRequestType;
use App\Modules\Compliance\Livewire\Concerns\AuthorizesCompliance;
use App\Modules\Crm\Enums\ConsentChannel;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Radicar la solicitud de un titular de datos. */
#[Layout('components.layouts.backoffice')]
final class DataRequestForm extends Component
{
    use AuthorizesCompliance;

    public string $type = '';

    public string $requester_name = '';

    public string $document_number = '';

    public string $email = '';

    public string $phone = '';

    public string $details = '';

    public string $channel = '';

    public string $received_on = '';

    public function mount(): void
    {
        $this->authorizeCompliance();
        $this->type = DataRequestType::Access->value;
        $this->channel = ConsentChannel::Email->value;
        $this->received_on = $this->today()->toDateString();
    }

    public function save(RegisterDataRequestAction $register): void
    {
        $this->authorizeCompliance();
        $validated = $this->validate([
            'type' => ['required', Rule::enum(DataRequestType::class)],
            'requester_name' => ['required', 'string', 'max:150'],
            'document_number' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'required_without:phone', 'email', 'max:150'],
            'phone' => ['nullable', 'required_without:email', 'string', 'regex:/^\+?[0-9 ()-]{7,20}$/'],
            'details' => ['required', 'string', 'max:5000'],
            'channel' => ['required', Rule::enum(ConsentChannel::class)],
            'received_on' => ['required', 'date', 'before_or_equal:today'],
        ], attributes: trans('compliance.requests.fields'));

        $timezone = config()->string('travel.agency.timezone');
        $receivedOn = CarbonImmutable::parse($validated['received_on'], $timezone);
        $receivedAt = $receivedOn->isSameDay($this->today()) ? CarbonImmutable::now($timezone) : $receivedOn->startOfDay();

        $request = $register->execute($this->actor(), new DataRequestData(
            DataRequestType::from($validated['type']),
            $validated['requester_name'],
            $validated['document_number'],
            $validated['email'] ?: null,
            $validated['phone'] ?: null,
            $validated['details'],
            ConsentChannel::from($validated['channel']),
            $receivedAt,
        ));

        session()->flash('status', __('compliance.requests.registered', [
            'number' => $request->number,
            'date' => $request->due_on->isoFormat('LL'),
        ]));
        $this->redirectRoute('compliance.requests.show', $request, navigate: true);
    }

    public function render(): View
    {
        return view('compliance::livewire.request-form', [
            'types' => collect(DataRequestType::cases())->mapWithKeys(static fn(DataRequestType $type): array => [$type->value => $type->label()])->all(),
            'channels' => collect(ConsentChannel::cases())->mapWithKeys(static fn(ConsentChannel $channel): array => [$channel->value => $channel->label()])->all(),
        ])->title(__('compliance.requests.create'))->layoutData(['heading' => __('compliance.requests.create')]);
    }
}
