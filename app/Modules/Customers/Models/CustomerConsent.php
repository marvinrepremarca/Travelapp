<?php

declare(strict_types=1);

namespace App\Modules\Customers\Models;

use App\Modules\Customers\Enums\ConsentChannel;
use App\Modules\Customers\Enums\ConsentPurpose;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Evidencia de autorización o revocación (Ley 1581). Solo inserción: un cambio es un registro nuevo.
 *
 * @property int $id
 * @property int $customer_id
 * @property ConsentPurpose $purpose
 * @property bool $granted
 * @property ConsentChannel $channel
 * @property string $policy_version
 * @property int $recorded_by
 * @property CarbonImmutable $recorded_at
 */
final class CustomerConsent extends Model
{
    public $timestamps = false;

    protected $fillable = ['purpose', 'granted', 'channel', 'policy_version', 'recorded_by', 'recorded_at'];

    protected static function booted(): void
    {
        self::updating(static fn(): never => throw new LogicException(__('customers.errors.consent_immutable')));
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'purpose' => ConsentPurpose::class,
            'channel' => ConsentChannel::class,
            'granted' => 'boolean',
            'recorded_at' => 'immutable_datetime',
        ];
    }
}
