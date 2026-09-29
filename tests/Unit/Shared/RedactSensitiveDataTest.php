<?php

declare(strict_types=1);

use App\Modules\Shared\Logging\RedactSensitiveData;
use Monolog\Level;
use Monolog\LogRecord;

function logRecord(array $context, array $extra = []): LogRecord
{
    return new LogRecord(new DateTimeImmutable(), 'test', Level::Info, 'message', $context, $extra);
}

it('masks sensitive keys at any depth', function (): void {
    $record = (new RedactSensitiveData())(logRecord([
        'booking' => 'BK-1',
        'Password' => 'secret-value',
        'traveler' => ['name' => 'ANA', 'passport_number' => 'AB123', 'birth_date' => '1990-01-01'],
        'headers' => ['Authorization' => 'Bearer x'],
    ], ['card_last4' => '4242']));

    expect($record->context)->toBe([
        'booking' => 'BK-1',
        'Password' => RedactSensitiveData::MASK,
        'traveler' => ['name' => 'ANA', 'passport_number' => RedactSensitiveData::MASK, 'birth_date' => RedactSensitiveData::MASK],
        'headers' => ['Authorization' => RedactSensitiveData::MASK],
    ])->and($record->extra)->toBe(['card_last4' => RedactSensitiveData::MASK]);
});

it('keeps non sensitive data and list values untouched', function (): void {
    $record = (new RedactSensitiveData())(logRecord(['items' => ['a', 'b'], 'status' => 'confirmed']));

    expect($record->context)->toBe(['items' => ['a', 'b'], 'status' => 'confirmed']);
});
