<?php

declare(strict_types=1);

namespace App\Modules\Organization\Livewire;

use App\Modules\Organization\Actions\UpdateAgencyProfileAction;
use App\Modules\Organization\Data\AgencyProfileData;
use App\Modules\Organization\Models\AgencyProfile;
use App\Modules\Organization\Rules\AccessibleBrandColor;
use App\Modules\Organization\Rules\ValidNitCheckDigit;
use App\Modules\Organization\Services\NitCheckDigit;
use App\Modules\Shared\Enums\Permission;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.backoffice')]
final class AgencyProfileForm extends Component
{
    use WithFileUploads;

    public string $legal_name = '';

    public string $trade_name = '';

    public string $nit = '';

    public string $nit_check_digit = '';

    public string $rnt_number = '';

    public string $rnt_expires_on = '';

    public string $address = '';

    public string $city = '';

    public string $phone = '';

    public string $email = '';

    public string $website = '';

    public string $brand_primary_color = '';

    public string $brand_accent_color = '';

    public ?UploadedFile $logo = null;

    public ?string $currentLogoUrl = null;

    public function mount(): void
    {
        Gate::authorize(Permission::OrganizationManage->value);

        $profile = AgencyProfile::current();

        if (!$profile instanceof \App\Modules\Organization\Models\AgencyProfile) {
            return;
        }

        $this->fill([
            'legal_name' => $profile->legal_name,
            'trade_name' => $profile->trade_name,
            'nit' => $profile->nit,
            'nit_check_digit' => (string) $profile->nit_check_digit,
            'rnt_number' => $profile->rnt_number,
            'rnt_expires_on' => $profile->rnt_expires_on->toDateString(),
            'address' => $profile->address,
            'city' => $profile->city,
            'phone' => $profile->phone,
            'email' => $profile->email,
            'website' => (string) $profile->website,
            'brand_primary_color' => (string) $profile->brand_primary_color,
            'brand_accent_color' => (string) $profile->brand_accent_color,
        ]);

        $this->currentLogoUrl = $this->logoUrl($profile);
    }

    public function save(UpdateAgencyProfileAction $update): void
    {
        Gate::authorize(Permission::OrganizationManage->value);

        $validated = $this->validate();

        $profile = $update->execute(new AgencyProfileData(
            legalName: $validated['legal_name'],
            tradeName: $validated['trade_name'],
            nit: $validated['nit'],
            rntNumber: $validated['rnt_number'],
            rntExpiresOn: CarbonImmutable::parse($validated['rnt_expires_on']),
            address: $validated['address'],
            city: $validated['city'],
            phone: $validated['phone'],
            email: $validated['email'],
            website: $validated['website'] ?: null,
            brandPrimaryColor: $validated['brand_primary_color'] ?: null,
            brandAccentColor: $validated['brand_accent_color'] ?: null,
            logo: $this->logo,
        ));

        $this->logo = null;
        $this->currentLogoUrl = $this->logoUrl($profile);

        session()->flash('status', __('organization.agency.saved'));
        $this->redirectRoute('organization.agency', navigate: true);
    }

    public function render(): View
    {
        return view('organization::livewire.agency-profile-form', [
            'rntWarning' => $this->rntWarning(),
        ])->title(__('organization.agency.title'))->layoutData(['heading' => __('organization.agency.title')]);
    }

    /** @return array<string, list<mixed>> */
    protected function rules(): array
    {
        $textOnBrand = config()->string('travel.organization.brand_text_color');

        return [
            'legal_name' => ['required', 'string', 'max:255'],
            'trade_name' => ['required', 'string', 'max:255'],
            'nit' => ['required', 'digits_between:5,' . NitCheckDigit::MAX_DIGITS],
            'nit_check_digit' => ['required', 'digits:1', new ValidNitCheckDigit($this->nit)],
            'rnt_number' => ['required', 'alpha_num', 'max:20'],
            'rnt_expires_on' => ['required', 'date'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255'],
            'website' => ['nullable', 'url:https', 'max:255'],
            'brand_primary_color' => ['nullable', new AccessibleBrandColor($textOnBrand)],
            'brand_accent_color' => ['nullable', new AccessibleBrandColor($textOnBrand)],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:' . config()->integer('travel.organization.logo_max_kb')],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        /** @var array<string, string> */
        return trans('organization.agency.fields');
    }

    private function rntWarning(): bool
    {
        if ($this->rnt_expires_on === '') {
            return false;
        }

        return CarbonImmutable::parse($this->rnt_expires_on)
            ->lessThanOrEqualTo(CarbonImmutable::today()->addDays(config()->integer('travel.organization.rnt_expiry_warning_days')));
    }

    private function logoUrl(AgencyProfile $profile): ?string
    {
        return $profile->logo_path === null
            ? null
            : Storage::disk(config()->string('travel.organization.logo_disk'))->url($profile->logo_path);
    }
}
