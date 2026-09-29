<?php

declare(strict_types=1);

use App\Modules\Shared\Enums\PassengerType;
use App\Modules\Shared\Exceptions\InvalidPassengerMix;
use App\Modules\Shared\ValueObjects\PassengerMix;

it('classifies ages at service date using the configured limits', function (int $age, PassengerType $type): void {
    expect(PassengerType::forAge($age))->toBe($type);
})->with([
    'newborn' => [0, PassengerType::Infant],
    'last infant age' => [1, PassengerType::Infant],
    'first child age' => [2, PassengerType::Child],
    'last child age' => [11, PassengerType::Child],
    'first adult age' => [12, PassengerType::Adult],
]);

it('follows configuration changes to age limits', function (): void {
    config(['travel.passengers.child_max_age' => 17]);

    expect(PassengerType::forAge(15))->toBe(PassengerType::Child);
});

it('builds a mix from ages', function (): void {
    $mix = PassengerMix::fromAges([35, 33, 8, 4, 1]);

    expect($mix->adults)->toBe(2)
        ->and($mix->childAges)->toBe([8, 4])
        ->and($mix->infants)->toBe(1)
        ->and($mix->total())->toBe(5)
        ->and($mix->occupying())->toBe(4)
        ->and($mix->countOf(PassengerType::Adult))->toBe(2)
        ->and($mix->countOf(PassengerType::Child))->toBe(2)
        ->and($mix->countOf(PassengerType::Infant))->toBe(1);
});

it('rejects invalid mixes', function (int $adults, array $childAges, int $infants, string $messageKey, array $replace = []): void {
    expect(fn(): \App\Modules\Shared\ValueObjects\PassengerMix => new PassengerMix($adults, $childAges, $infants))
        ->toThrow(InvalidPassengerMix::class, __($messageKey, $replace));
})->with([
    'no adults' => [0, [5], 0, 'shared.errors.passenger_mix_no_adults'],
    'more infants than adults' => [1, [], 2, 'shared.errors.passenger_mix_too_many_infants', ['infants' => 2, 'adults' => 1]],
    'negative adults' => [-1, [], 0, 'shared.errors.passenger_mix_negative'],
    'negative infants' => [1, [], -1, 'shared.errors.passenger_mix_negative'],
    'negative child age' => [1, [-3], 0, 'shared.errors.passenger_mix_negative'],
]);

it('exposes a stable error code', function (): void {
    expect(InvalidPassengerMix::noAdults()->errorCode())->toBe('invalid_passenger_mix');
});

it('labels shared enums in spanish', function (): void {
    expect(PassengerType::Infant->label())->toBe('Infante')
        ->and(\App\Modules\Shared\Enums\ProductType::DayTrip->label())->toBe('Pasadía')
        ->and(\App\Modules\Shared\Enums\SalesChannel::WhatsApp->label())->toBe('WhatsApp');
});
