<?php

declare(strict_types=1);

namespace App\Modules\Customers\Livewire;

use App\Modules\Customers\Actions\SaveTravelerAction;
use App\Modules\Customers\Data\TravelerData;
use App\Modules\Customers\Enums\Gender;
use App\Modules\Customers\Models\Customer;
use App\Modules\Customers\Models\Traveler;
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
final class TravelerForm extends Component
{
    #[Locked]
    public string $customerUlid;

    #[Locked]
    public ?string $travelerUlid = null;

    public string $first_name = '';

    public string $last_name = '';

    public string $gender = '';

    /** En edición queda vacío: solo se escribe para cambiarla. */
    public string $birth_date = '';

    public string $nationality = 'CO';

    public string $passport_number = '';

    public string $passport_country = '';

    public string $passport_expires_on = '';

    public function mount(Customer $customer, ?Traveler $traveler = null): void
    {
        Gate::authorize('update', $customer);
        $this->customerUlid = $customer->ulid;

        if (! $traveler?->exists) {
            return;
        }

        abort_unless($traveler->customer_id === $customer->id, 404);
        $this->travelerUlid = $traveler->ulid;
        $this->fill([
            'first_name' => $traveler->first_name,
            'last_name' => $traveler->last_name,
            'gender' => $traveler->gender->value,
            'nationality' => $traveler->nationality,
            'passport_country' => (string) $traveler->passport_country,
            'passport_expires_on' => (string) $traveler->passport_expires_on?->toDateString(),
        ]);
    }

    public function save(SaveTravelerAction $save): void
    {
        $customer = $this->customer();
        Gate::authorize('update', $customer);
        $traveler = $this->traveler($customer);

        $validated = $this->validate($this->rules($traveler instanceof Traveler), attributes: $this->attributes());

        $save->execute($customer, new TravelerData(
            firstName: $validated['first_name'],
            lastName: $validated['last_name'],
            gender: Gender::from($validated['gender']),
            birthDate: $validated['birth_date'] !== '' ? CarbonImmutable::parse($validated['birth_date']) : ($traveler instanceof Traveler ? $traveler->birth_date : CarbonImmutable::today()),
            nationality: $validated['nationality'],
            passportNumber: $validated['passport_number'] ?: null,
            passportCountry: $validated['passport_country'] ?: null,
            passportExpiresOn: $validated['passport_expires_on'] !== '' ? CarbonImmutable::parse($validated['passport_expires_on']) : null,
        ), $traveler);

        session()->flash('status', __('customers.travelers.saved'));
        $this->redirectRoute('customers.show', $customer, navigate: true);
    }

    public function render(): View
    {
        $title = $this->travelerUlid === null ? __('customers.travelers.create') : __('customers.travelers.edit');

        return view('customers::livewire.traveler-form', [
            'genders' => Gender::cases(),
            'isEditing' => $this->travelerUlid !== null,
            'customer' => $this->customer(),
        ])->title($title)->layoutData(['heading' => $title]);
    }

    /** @return array<string, list<mixed>> */
    protected function rules(bool $isEditing = false): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'birth_date' => [$isEditing ? 'nullable' : 'required', 'date', 'before_or_equal:today'],
            'nationality' => ['required', 'string', 'size:2', 'alpha'],
            'passport_number' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9]+$/'],
            'passport_country' => ['nullable', 'required_with:passport_number', 'string', 'size:2', 'alpha'],
            'passport_expires_on' => ['nullable', 'required_with:passport_number', 'date', 'after:today'],
        ];
    }

    /** @return array<string, string> */
    private function attributes(): array
    {
        /** @var array<string, string> */
        return trans('customers.travelers.fields');
    }

    private function customer(): Customer
    {
        return Customer::query()->visibleTo($this->actor())->where('ulid', $this->customerUlid)->first() ?? abort(404);
    }

    private function traveler(Customer $customer): ?Traveler
    {
        return $this->travelerUlid === null
            ? null
            : Traveler::query()->where('customer_id', $customer->id)->where('ulid', $this->travelerUlid)->first() ?? abort(404);
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
