<?php

declare(strict_types=1);

namespace App\Modules\Crm\Livewire;

use App\Modules\Crm\Actions\CreateCustomerAction;
use App\Modules\Crm\Actions\UpdateCustomerAction;
use App\Modules\Crm\Data\ConsentData;
use App\Modules\Crm\Data\CustomerData;
use App\Modules\Crm\Enums\ConsentChannel;
use App\Modules\Crm\Enums\CustomerType;
use App\Modules\Crm\Enums\DocumentType;
use App\Modules\Crm\Exceptions\CustomerRuleViolation;
use App\Modules\Crm\Models\Customer;
use App\Modules\Identity\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.backoffice')]
final class CustomerForm extends Component
{
    #[Locked]
    public ?string $customerUlid = null;

    public string $type = 'person';

    public string $first_name = '';

    public string $last_name = '';

    public string $legal_name = '';

    public string $document_type = 'cc';

    /** En edición queda vacío: solo se escribe para cambiar el documento. */
    public string $document_number = '';

    public string $birth_date = '';

    public string $email = '';

    public string $phone = '';

    public string $city = '';

    public string $country = '';

    public string $notes = '';

    public bool $consent_data_processing = false;

    public bool $consent_marketing = false;

    public string $consent_channel = 'in_person';

    public bool $possibleDuplicate = false;

    public function mount(?Customer $customer = null): void
    {
        if (! $customer?->exists) {
            return;
        }

        Gate::authorize('update', $customer);
        $this->customerUlid = $customer->ulid;
        $this->fill([
            'type' => $customer->type->value,
            'first_name' => (string) $customer->first_name,
            'last_name' => (string) $customer->last_name,
            'legal_name' => (string) $customer->legal_name,
            'document_type' => $customer->document_type->value,
            'email' => (string) $customer->email,
            'phone' => (string) $customer->phone,
            'city' => (string) $customer->city,
            'country' => (string) $customer->country,
            'notes' => (string) $customer->notes,
        ]);
    }

    /** Aviso de posible duplicado por correo o teléfono, sin revelar de quién es. */
    public function updated(string $property): void
    {
        if (! in_array($property, ['email', 'phone'], true)) {
            return;
        }

        $this->possibleDuplicate = Customer::query()
            ->when($this->customerUlid !== null, fn($query) => $query->where('ulid', '!=', $this->customerUlid))
            ->where(fn($query) => $query
                ->when($this->email !== '', fn($inner) => $inner->orWhere('email', mb_strtolower($this->email)))
                ->when($this->phone !== '', fn($inner) => $inner->orWhere('phone', $this->phone)))
            ->when($this->email === '' && $this->phone === '', fn($query) => $query->whereRaw('1 = 0'))
            ->exists();
    }

    public function save(CreateCustomerAction $create, UpdateCustomerAction $update): void
    {
        $customer = $this->customer();
        $isEditing = $customer instanceof Customer;

        if ($isEditing) {
            Gate::authorize('update', $customer);
        }

        $validated = $this->validate($this->rules($isEditing), attributes: $this->attributes());
        $type = CustomerType::from($validated['type']);
        $documentType = DocumentType::from($validated['document_type']);

        $data = new CustomerData(
            type: $type,
            documentType: $documentType,
            documentNumber: $validated['document_number'] !== '' ? $validated['document_number'] : (string) $customer?->document_number,
            firstName: $type === CustomerType::Person ? $validated['first_name'] : null,
            lastName: $type === CustomerType::Person ? $validated['last_name'] : null,
            legalName: $type === CustomerType::Company ? $validated['legal_name'] : null,
            birthDate: $type === CustomerType::Person && $validated['birth_date'] !== '' ? CarbonImmutable::parse($validated['birth_date']) : ($isEditing ? $customer->birth_date : null),
            email: $validated['email'] ?: null,
            phone: $validated['phone'] ?: null,
            city: $validated['city'] ?: null,
            country: $validated['country'] ?: null,
            notes: $validated['notes'] ?: null,
        );

        try {
            $saved = $isEditing
                ? $update->execute($customer, $data)
                : $create->execute($data, new ConsentData($this->consent_data_processing, $this->consent_marketing, ConsentChannel::from($validated['consent_channel'])), $this->actor());
        } catch (CustomerRuleViolation $violation) {
            $this->addError(match ($violation->errorCode()) {
                'consent_required' => 'consent_data_processing',
                default => 'document_number',
            }, $violation->getMessage());

            return;
        }

        session()->flash('status', __($isEditing ? 'crm.customers.saved' : 'crm.customers.created', ['name' => $saved->display_name]));
        $this->redirectRoute('crm.customers.show', $saved, navigate: true);
    }

    public function render(): View
    {
        $type = CustomerType::tryFrom($this->type) ?? CustomerType::Person;
        $title = $this->customerUlid === null ? __('crm.customers.create') : __('crm.customers.edit');

        return view('crm::livewire.customer-form', [
            'types' => CustomerType::cases(),
            'documentTypes' => $type->allowedDocuments(),
            'channels' => ConsentChannel::cases(),
            'isEditing' => $this->customerUlid !== null,
            'isPerson' => $type === CustomerType::Person,
            'policyVersion' => config('travel.privacy.policy_version'),
        ])->title($title)->layoutData(['heading' => $title]);
    }

    /** @return array<string, list<mixed>> */
    protected function rules(bool $isEditing = false): array
    {
        $isPerson = $this->type === CustomerType::Person->value;

        return [
            'type' => ['required', Rule::enum(CustomerType::class)],
            'first_name' => [$isPerson ? 'required' : 'nullable', 'string', 'max:100'],
            'last_name' => [$isPerson ? 'required' : 'nullable', 'string', 'max:100'],
            'legal_name' => [$isPerson ? 'nullable' : 'required', 'string', 'max:255'],
            'document_type' => ['required', Rule::enum(DocumentType::class)],
            'document_number' => [$isEditing ? 'nullable' : 'required', 'string', 'max:30', 'regex:/^[A-Za-z0-9.\-\s]+$/'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'size:2', 'alpha'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'consent_channel' => ['required', Rule::enum(ConsentChannel::class)],
        ];
    }

    /** @return array<string, string> */
    private function attributes(): array
    {
        /** @var array<string, string> */
        return trans('crm.customers.fields');
    }

    private function customer(): ?Customer
    {
        return $this->customerUlid === null
            ? null
            : Customer::query()->visibleTo($this->actor())->where('ulid', $this->customerUlid)->first() ?? abort(404);
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
