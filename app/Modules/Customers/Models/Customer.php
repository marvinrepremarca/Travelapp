<?php

declare(strict_types=1);

namespace App\Modules\Customers\Models;

use App\Modules\Customers\Database\Factories\CustomerFactory;
use App\Modules\Customers\Enums\ConsentPurpose;
use App\Modules\Customers\Enums\CustomerType;
use App\Modules\Customers\Enums\DocumentType;
use App\Modules\Shared\Casts\EncryptedDate;
use App\Modules\Shared\Concerns\HasVisibilityScope;
use App\Modules\Shared\Enums\AuditLogName;
use App\Modules\Shared\Support\Mask;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Cliente (quien compra): persona o empresa. Documento y fecha de nacimiento cifrados en reposo.
 *
 * @property int $id
 * @property string $ulid
 * @property CustomerType $type
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $legal_name
 * @property string $display_name
 * @property DocumentType $document_type
 * @property string $document_number
 * @property string $document_hash
 * @property CarbonImmutable|null $birth_date
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $city
 * @property string|null $country
 * @property string|null $notes
 * @property int $owner_id
 * @property int|null $branch_id
 */
final class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;
    use HasUlids;
    use HasVisibilityScope;
    use LogsActivity;
    use SoftDeletes;

    /** Documento (y su huella), responsable y sucursal se asignan solo en las Actions. */
    protected $fillable = ['type', 'first_name', 'last_name', 'legal_name', 'birth_date', 'email', 'phone', 'city', 'country', 'notes'];

    /** Nunca se serializan en claro. */
    protected $hidden = ['document_number', 'document_hash', 'birth_date'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function maskedDocument(): string
    {
        return Mask::value($this->document_number);
    }

    /** @return HasMany<CustomerConsent, $this> */
    public function consents(): HasMany
    {
        return $this->hasMany(CustomerConsent::class)->latest('recorded_at')->latest('id');
    }

    /** @return HasMany<Traveler, $this> */
    public function travelers(): HasMany
    {
        return $this->hasMany(Traveler::class)->orderBy('first_name');
    }

    /** Autorización vigente = el último registro de esa finalidad. */
    public function hasConsent(ConsentPurpose $purpose): bool
    {
        return CustomerConsent::query()
            ->where('customer_id', $this->id)
            ->where('purpose', $purpose)
            ->latest('recorded_at')
            ->latest('id')
            ->value('granted') === true;
    }

    /** La bitácora nunca guarda el documento ni la fecha de nacimiento: solo que cambiaron. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['type', 'display_name', 'document_type', 'document_hash', 'email', 'phone', 'city', 'owner_id', 'branch_id'])
            ->logOnlyDirty()
            ->useLogName(AuditLogName::Crm->value);
    }

    protected static function newFactory(): CustomerFactory
    {
        return CustomerFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => CustomerType::class,
            'document_type' => DocumentType::class,
            'document_number' => 'encrypted',
            'birth_date' => EncryptedDate::class,
        ];
    }
}
