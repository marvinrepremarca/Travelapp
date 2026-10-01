<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Database\Factories\CatalogProductFactory;
use App\Modules\Shared\Enums\AuditLogName;
use App\Modules\Shared\Enums\ProductType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Producto propio de la agencia (tour, pasadía, traslado, actividad). Su costo neto vive en las tarifas por temporada.
 *
 * @property int $id
 * @property string $ulid
 * @property string $code
 * @property string $name
 * @property ProductType $product_type
 * @property string|null $description
 * @property string $destination_country
 * @property string $destination_city
 * @property string $timezone
 * @property int|null $duration_minutes
 * @property int|null $supplier_id
 * @property string $currency
 * @property bool $is_active
 */
final class CatalogProduct extends Model
{
    /** @use HasFactory<CatalogProductFactory> */
    use HasFactory;
    use HasUlids;
    use LogsActivity;

    /** Tipos que la agencia opera como producto propio y que pueden formar parte de un paquete. */
    public const OWN_PRODUCT_TYPES = [ProductType::Tour, ProductType::DayTrip, ProductType::Transfer, ProductType::Activity];

    /** Tipos que se pueden crear en el catálogo (los anteriores más el paquete prearmado). */
    public const CATALOG_TYPES = [...self::OWN_PRODUCT_TYPES, ProductType::Package];

    protected $fillable = [
        'code', 'name', 'product_type', 'description', 'destination_country', 'destination_city', 'timezone',
        'duration_minutes', 'supplier_id', 'currency',
    ];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /** @return HasMany<CatalogSeason, $this> */
    public function seasons(): HasMany
    {
        return $this->hasMany(CatalogSeason::class, 'product_id');
    }

    /** @return HasMany<CatalogPackageComponent, $this> */
    public function components(): HasMany
    {
        return $this->hasMany(CatalogPackageComponent::class, 'package_id');
    }

    public function isPackage(): bool
    {
        return $this->product_type === ProductType::Package;
    }

    /** @return HasMany<CatalogDeparture, $this> */
    public function departures(): HasMany
    {
        return $this->hasMany(CatalogDeparture::class, 'product_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly([...$this->fillable, 'is_active'])->logOnlyDirty()->useLogName(AuditLogName::Catalog->value);
    }

    protected static function newFactory(): CatalogProductFactory
    {
        return CatalogProductFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'product_type' => ProductType::class,
            'duration_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
