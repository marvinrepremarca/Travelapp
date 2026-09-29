<?php

declare(strict_types=1);

use App\Modules\Shared\Exceptions\InvalidDateRange;
use App\Modules\Shared\ValueObjects\DateRange;
use Carbon\CarbonImmutable;

it('counts nights by calendar dates', function (string $start, string $end, int $nights): void {
    $range = DateRange::fromStrings($start, $end);

    expect($range->nights())->toBe($nights)
        ->and($range->days())->toBe($nights + 1);
})->with([
    'same day (pasadía)' => ['2026-10-12', '2026-10-12', 0],
    'one night' => ['2026-10-12', '2026-10-13', 1],
    'crosses month' => ['2026-10-30', '2026-11-02', 3],
    'leap day' => ['2028-02-28', '2028-03-01', 2],
]);

it('ignores the time of day and daylight saving changes', function (): void {
    $start = CarbonImmutable::parse('2026-03-07 23:00', 'America/New_York');
    $end = CarbonImmutable::parse('2026-03-09 01:00', 'America/New_York');

    expect((new DateRange($start, $end))->nights())->toBe(2);
});

it('rejects an end before the start', function (): void {
    expect(fn(): \App\Modules\Shared\ValueObjects\DateRange => DateRange::fromStrings('2026-10-13', '2026-10-12'))
        ->toThrow(InvalidDateRange::class, __('shared.errors.date_range_end_before_start', ['start' => '2026-10-13', 'end' => '2026-10-12']));
});

it('exposes a stable error code', function (): void {
    expect(InvalidDateRange::endBeforeStart(CarbonImmutable::now(), CarbonImmutable::yesterday())->errorCode())->toBe('invalid_date_range');
});

it('contains its first and last day', function (string $date, bool $expected): void {
    expect(DateRange::fromStrings('2026-10-12', '2026-10-15')->contains(CarbonImmutable::parse($date)))->toBe($expected);
})->with([
    'day before' => ['2026-10-11', false],
    'first day' => ['2026-10-12 18:30', true],
    'last day' => ['2026-10-15', true],
    'day after' => ['2026-10-16', false],
]);

it('overlaps only when sharing a night', function (string $start, string $end, bool $expected): void {
    $stay = DateRange::fromStrings('2026-10-12', '2026-10-15');

    expect($stay->overlaps(DateRange::fromStrings($start, $end)))->toBe($expected)
        ->and(DateRange::fromStrings($start, $end)->overlaps($stay))->toBe($expected);
})->with([
    'back to back after' => ['2026-10-15', '2026-10-17', false],
    'back to back before' => ['2026-10-10', '2026-10-12', false],
    'one shared night' => ['2026-10-14', '2026-10-16', true],
    'inside' => ['2026-10-13', '2026-10-14', true],
]);

it('lists each night for per-night rates', function (): void {
    $nights = DateRange::fromStrings('2026-12-30', '2027-01-02')->eachNight();

    expect(array_map(static fn(CarbonImmutable $night): string => $night->toDateString(), $nights))
        ->toBe(['2026-12-30', '2026-12-31', '2027-01-01']);
});

it('compares by value', function (): void {
    expect(DateRange::fromStrings('2026-10-12', '2026-10-15')->equals(DateRange::fromStrings('2026-10-12 10:00', '2026-10-15')))->toBeTrue()
        ->and(DateRange::fromStrings('2026-10-12', '2026-10-15')->equals(DateRange::fromStrings('2026-10-12', '2026-10-16')))->toBeFalse();
});
