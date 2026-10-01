<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use App\Modules\Pricing\Enums\ExchangeRateSource;
use App\Modules\Shared\Enums\AuditLogName;
use Brick\Math\BigDecimal;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Tasa de cambio de una fecha: 1 unidad de base_currency = rate unidades de quote_currency.
 *
 * @property int $id
 * @property string $base_currency
 * @property string $quote_currency
 * @property string $rate
 * @property ExchangeRateSource $source
 * @property CarbonImmutable $valid_on
 * @property int|null $recorded_by
 */
final class ExchangeRate extends Model
{
    use LogsActivity;

    protected $fillable = ['base_currency', 'quote_currency', 'rate', 'source', 'valid_on', 'recorded_by'];

    public function decimalRate(): BigDecimal
    {
        return BigDecimal::of($this->rate);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->useLogName(AuditLogName::Pricing->value);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'source' => ExchangeRateSource::class,
            'valid_on' => 'immutable_date',
        ];
    }
}
