<?php

declare(strict_types=1);

namespace App\Modules\Customers\Models;

use App\Modules\Customers\Database\Factories\TravelerFactory;
use App\Modules\Customers\Enums\Gender;
use App\Modules\Shared\Casts\EncryptedDate;
use App\Modules\Shared\Enums\AuditLogName;
use App\Modules\Shared\Enums\PassengerType;
use App\Modules\Shared\Support\Mask;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Pasajero (quien viaja) asociado a un cliente. Su visibilidad es la del cliente.
 *
 * @property int $id
 * @property string $ulid
 * @property int $customer_id
 * @property string $first_name
 * @property string $last_name
 * @property Gender $gender
 * @property CarbonImmutable $birth_date
 * @property string $nationality
 * @property string|null $passport_number
 * @property string|null $passport_hash
 * @property string|null $passport_country
 * @property CarbonImmutable|null $passport_expires_on
 */
final class Traveler extends Model
{
    /** @use HasFactory<TravelerFactory> */
    use HasFactory;
    use HasUlids;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = ['first_name', 'last_name', 'gender', 'birth_date', 'nationality', 'passport_country', 'passport_expires_on'];

    protected $hidden = ['birth_date', 'passport_number', 'passport_hash'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function fullName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /** Nombre como lo piden las aerolíneas: mayúsculas y sin tildes (APELLIDO/NOMBRE). */
    public function airlineName(): string
    {
        return mb_strtoupper(Str::ascii($this->last_name)) . '/' . mb_strtoupper(Str::ascii($this->first_name));
    }

    /** Edad cumplida a la fecha del servicio, no a hoy. */
    public function ageAt(CarbonImmutable $serviceDate): int
    {
        return (int) $this->birth_date->diffInYears($serviceDate->startOfDay(), absolute: true);
    }

    public function passengerTypeAt(CarbonImmutable $serviceDate): PassengerType
    {
        return PassengerType::forAge($this->ageAt($serviceDate));
    }

    /** El pasaporte debe seguir vigente cierto tiempo después del regreso ⚙. */
    public function passportValidFor(CarbonImmutable $returnDate, int $minimumMonths): bool
    {
        return $this->passport_expires_on !== null
            && $this->passport_expires_on->greaterThanOrEqualTo($returnDate->addMonthsNoOverflow($minimumMonths));
    }

    public function maskedPassport(): string
    {
        return Mask::value($this->passport_number);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['first_name', 'last_name', 'gender', 'nationality', 'passport_hash', 'passport_expires_on'])
            ->logOnlyDirty()
            ->useLogName(AuditLogName::Crm->value);
    }

    protected static function newFactory(): TravelerFactory
    {
        return TravelerFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'birth_date' => EncryptedDate::class,
            'passport_number' => 'encrypted',
            'passport_expires_on' => 'immutable_date',
        ];
    }
}
