<?php

declare(strict_types=1);

namespace App\Modules\Search\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Aeropuerto de referencia (dato maestro, solo lectura desde la aplicación).
 *
 * @property int $id
 * @property string $iata_code
 * @property string $city
 * @property string $name
 * @property string $country_code
 * @property int $priority
 * @property string $search_text
 */
final class Airport extends Model
{
    public $timestamps = false;

    protected $fillable = [];
}
