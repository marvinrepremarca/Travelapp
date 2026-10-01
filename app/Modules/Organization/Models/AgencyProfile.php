<?php

declare(strict_types=1);

namespace App\Modules\Organization\Models;

use App\Modules\Organization\Database\Factories\AgencyProfileFactory;
use App\Modules\Shared\Enums\AuditLogName;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Datos legales y de marca de la agencia (un único registro).
 *
 * @property int $id
 * @property string $legal_name
 * @property string $trade_name
 * @property string $nit
 * @property int $nit_check_digit
 * @property string $rnt_number
 * @property CarbonImmutable $rnt_expires_on
 * @property string $address
 * @property string $city
 * @property string $phone
 * @property string $email
 * @property string|null $website
 * @property string|null $logo_path
 * @property string|null $brand_primary_color
 * @property string|null $brand_accent_color
 */
final class AgencyProfile extends Model
{
    /** @use HasFactory<AgencyProfileFactory> */
    use HasFactory;
    use LogsActivity;

    protected $table = 'agency_profile';

    protected $fillable = [
        'legal_name', 'trade_name', 'nit', 'nit_check_digit', 'rnt_number', 'rnt_expires_on',
        'address', 'city', 'phone', 'email', 'website', 'logo_path', 'brand_primary_color', 'brand_accent_color',
    ];

    /** Una sola lectura por request: el perfil se consulta desde el layout y desde las pantallas. */
    public static function current(): ?self
    {
        return once(static fn(): ?self => self::query()->first());
    }

    public function formattedNit(): string
    {
        return number_format((int) $this->nit, 0, '', '.') . '-' . $this->nit_check_digit;
    }

    public function rntExpiresWithin(int $days, CarbonImmutable $today): bool
    {
        return $this->rnt_expires_on->lessThanOrEqualTo($today->addDays($days));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->useLogName(AuditLogName::Organization->value);
    }

    protected static function newFactory(): AgencyProfileFactory
    {
        return AgencyProfileFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'nit_check_digit' => 'integer',
            'rnt_expires_on' => 'immutable_date',
        ];
    }
}
