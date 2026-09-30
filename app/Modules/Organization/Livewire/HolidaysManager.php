<?php

declare(strict_types=1);

namespace App\Modules\Organization\Livewire;

use App\Modules\Organization\Actions\CreateHolidayAdjustmentAction;
use App\Modules\Organization\Actions\DeleteHolidayAdjustmentAction;
use App\Modules\Organization\Contracts\HolidayCalendar;
use App\Modules\Organization\Data\HolidayAdjustmentData;
use App\Modules\Organization\Enums\HolidayAdjustmentType;
use App\Modules\Organization\Exceptions\InvalidHolidayAdjustment;
use App\Modules\Organization\Models\HolidayAdjustment;
use App\Modules\Shared\Enums\Permission;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.backoffice')]
final class HolidaysManager extends Component
{
    private const YEARS_AROUND = 1;

    #[Url]
    public int $year = 0;

    public string $date = '';

    public string $type = '';

    public string $name = '';

    public function mount(): void
    {
        Gate::authorize(Permission::OrganizationManage->value);

        if ($this->year === 0) {
            $this->year = CarbonImmutable::today()->year;
        }

        $this->type = HolidayAdjustmentType::Add->value;
    }

    public function addAdjustment(CreateHolidayAdjustmentAction $create): void
    {
        Gate::authorize(Permission::OrganizationManage->value);

        $validated = $this->validate([
            'date' => ['required', 'date', Rule::unique('holiday_adjustments', 'date')],
            'type' => ['required', Rule::enum(HolidayAdjustmentType::class)],
            'name' => ['required', 'string', 'max:120'],
        ], attributes: $this->attributes());

        try {
            $create->execute(new HolidayAdjustmentData(
                CarbonImmutable::parse($validated['date']),
                HolidayAdjustmentType::from($validated['type']),
                $validated['name'],
            ));
        } catch (InvalidHolidayAdjustment $exception) {
            $this->addError('date', $exception->getMessage());

            return;
        }

        $this->reset('date', 'name');
        session()->flash('status', __('organization.holidays_screen.saved'));
    }

    public function removeAdjustment(string $ulid, DeleteHolidayAdjustmentAction $delete): void
    {
        Gate::authorize(Permission::OrganizationManage->value);

        $delete->execute(HolidayAdjustment::query()->where('ulid', $ulid)->firstOrFail());
        session()->flash('status', __('organization.holidays_screen.removed'));
    }

    public function render(HolidayCalendar $calendar): View
    {
        $currentYear = CarbonImmutable::today()->year;

        return view('organization::livewire.holidays-manager', [
            'holidays' => $calendar->holidaysIn($this->year),
            'adjustments' => HolidayAdjustment::query()->whereYear('date', $this->year)->orderBy('date')->get(),
            'years' => range($currentYear - self::YEARS_AROUND, $currentYear + self::YEARS_AROUND + 1),
            'types' => HolidayAdjustmentType::cases(),
        ])->title(__('organization.holidays_screen.title'))
            ->layoutData(['heading' => __('organization.holidays_screen.title')]);
    }

    /** @return array<string, string> */
    private function attributes(): array
    {
        /** @var array<string, string> */
        return trans('organization.holidays_screen.fields');
    }
}
