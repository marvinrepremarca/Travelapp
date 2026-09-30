<?php

declare(strict_types=1);

use App\Modules\Organization\Data\Holiday;
use App\Modules\Organization\Enums\HolidaySource;
use App\Modules\Organization\Services\ColombianHolidayCalculator;

it('calculates easter sunday', function (int $year, string $easter): void {
    expect((new ColombianHolidayCalculator())->easterSunday($year)->toDateString())->toBe($easter);
})->with([
    [2024, '2024-03-31'],
    [2025, '2025-04-20'],
    [2026, '2026-04-05'],
    [2027, '2027-03-28'],
    [2038, '2038-04-25'],
]);

it('lists the official colombian holidays of 2026', function (): void {
    $holidays = (new ColombianHolidayCalculator())->forYear(2026);

    expect(array_map(static fn(Holiday $holiday): string => $holiday->date->toDateString(), $holidays))->toBe([
        '2026-01-01', '2026-01-12', '2026-03-23', '2026-04-02', '2026-04-03', '2026-05-01',
        '2026-05-18', '2026-06-08', '2026-06-15', '2026-06-29', '2026-07-20', '2026-08-07',
        '2026-08-17', '2026-10-12', '2026-11-02', '2026-11-16', '2026-12-08', '2026-12-25',
    ]);
});

it('moves emiliani holidays to the next monday and names them', function (): void {
    $holidays = collect((new ColombianHolidayCalculator())->forYear(2025))
        ->mapWithKeys(static fn(Holiday $holiday): array => [$holiday->date->toDateString() => $holiday->name]);

    expect($holidays->get('2025-01-06'))->toBe('Día de los Reyes Magos')
        ->and($holidays->get('2025-06-02'))->toBe('Ascensión del Señor')
        ->and($holidays->get('2025-06-23'))->toBe('Corpus Christi')
        ->and($holidays->get('2025-06-30'))->toBe('Sagrado Corazón')
        ->and($holidays->get('2025-11-03'))->toBe('Todos los Santos');
});

it('marks calculated holidays as national', function (): void {
    foreach ((new ColombianHolidayCalculator())->forYear(2027) as $holiday) {
        expect($holiday->source)->toBe(HolidaySource::National);
    }
});
