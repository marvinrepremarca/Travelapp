<?php

declare(strict_types=1);

use App\Modules\Organization\Actions\CreateHolidayAdjustmentAction;
use App\Modules\Organization\Actions\DeleteHolidayAdjustmentAction;
use App\Modules\Organization\Contracts\HolidayCalendar;
use App\Modules\Organization\Data\Holiday;
use App\Modules\Organization\Data\HolidayAdjustmentData;
use App\Modules\Organization\Enums\HolidayAdjustmentType;
use App\Modules\Organization\Enums\HolidaySource;
use App\Modules\Organization\Exceptions\InvalidHolidayAdjustment;
use App\Modules\Organization\Models\HolidayAdjustment;
use App\Modules\Shared\Enums\AuditLogName;
use Carbon\CarbonImmutable;
use Spatie\Activitylog\Models\Activity;

function calendar(): HolidayCalendar
{
    return app(HolidayCalendar::class);
}

function adjust(string $date, HolidayAdjustmentType $type, string $name = 'Ajuste'): HolidayAdjustment
{
    return app(CreateHolidayAdjustmentAction::class)->execute(new HolidayAdjustmentData(CarbonImmutable::parse($date), $type, $name));
}

it('knows national holidays and business days', function (string $date, bool $holiday, bool $business): void {
    $day = CarbonImmutable::parse($date);

    expect(calendar()->isHoliday($day))->toBe($holiday)
        ->and(calendar()->isBusinessDay($day))->toBe($business);
})->with([
    'monday holiday' => ['2026-01-12', true, false],
    'regular tuesday' => ['2026-01-13', false, true],
    'saturday' => ['2026-01-17', false, false],
    'good friday' => ['2026-04-03', true, false],
]);

it('adds business days skipping weekends and holidays', function (string $from, int $days, string $expected): void {
    expect(calendar()->addBusinessDays(CarbonImmutable::parse($from), $days)->toDateString())->toBe($expected);
})->with([
    'zero days' => ['2026-01-09', 0, '2026-01-09'],
    'over a weekend and a monday holiday' => ['2026-01-09', 1, '2026-01-13'],
    'over holy week' => ['2026-04-01', 2, '2026-04-07'],
]);

it('rejects negative business days', function (): void {
    calendar()->addBusinessDays(CarbonImmutable::parse('2026-01-09'), -1);
})->throws(InvalidArgumentException::class);

it('adds an agency day off and audits it', function (): void {
    expect(calendar()->isHoliday(CarbonImmutable::parse('2026-12-24')))->toBeFalse();

    adjust('2026-12-24', HolidayAdjustmentType::Add, 'Cierre de Nochebuena');

    $holiday = collect(calendar()->holidaysIn(2026))->first(fn(Holiday $h): bool => $h->date->toDateString() === '2026-12-24');
    expect($holiday?->source)->toBe(HolidaySource::Agency)
        ->and($holiday?->name)->toBe('Cierre de Nochebuena')
        ->and(Activity::query()->where('log_name', AuditLogName::Organization->value)->count())->toBe(1);
});

it('removes a national holiday when the agency works that day', function (): void {
    adjust('2026-01-12', HolidayAdjustmentType::Remove);

    expect(calendar()->isHoliday(CarbonImmutable::parse('2026-01-12')))->toBeFalse()
        ->and(calendar()->holidaysIn(2026))->toHaveCount(17);
});

it('restores the calendar when an adjustment is deleted', function (): void {
    $adjustment = adjust('2026-01-12', HolidayAdjustmentType::Remove);
    expect(calendar()->isHoliday(CarbonImmutable::parse('2026-01-12')))->toBeFalse();

    app(DeleteHolidayAdjustmentAction::class)->execute($adjustment);

    expect(calendar()->isHoliday(CarbonImmutable::parse('2026-01-12')))->toBeTrue()
        ->and(HolidayAdjustment::query()->count())->toBe(0);
});

it('rejects inconsistent adjustments', function (string $date, HolidayAdjustmentType $type, string $messageKey): void {
    expect(fn(): \App\Modules\Organization\Models\HolidayAdjustment => adjust($date, $type))
        ->toThrow(InvalidHolidayAdjustment::class, __($messageKey, ['date' => $date]));
})->with([
    'remove a normal day' => ['2026-01-13', HolidayAdjustmentType::Remove, 'organization.errors.not_a_national_holiday'],
    'add a national holiday' => ['2026-01-12', HolidayAdjustmentType::Add, 'organization.errors.already_a_national_holiday'],
]);

it('exposes a stable error code and translated labels', function (): void {
    expect(InvalidHolidayAdjustment::notANationalHoliday(CarbonImmutable::now())->errorCode())->toBe('invalid_holiday_adjustment')
        ->and(HolidayAdjustmentType::Add->label())->toBe(__('organization.holiday_adjustment_type.add'))
        ->and(HolidaySource::Agency->label())->toBe(__('organization.holiday_source.agency'))
        ->and(HolidaySource::National->tone()->value)->toBe('info')
        ->and(HolidaySource::Agency->tone()->value)->toBe('neutral');
});

it('builds adjustments with factory states', function (): void {
    expect(HolidayAdjustment::factory()->removing('2026-01-12')->make()->type)->toBe(HolidayAdjustmentType::Remove)
        ->and(HolidayAdjustment::factory()->adding('2026-12-24')->create()->getRouteKey())->toHaveLength(26);
});
