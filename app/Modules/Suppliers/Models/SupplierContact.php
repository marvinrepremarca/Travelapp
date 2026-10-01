<?php

declare(strict_types=1);

namespace App\Modules\Suppliers\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $supplier_id
 * @property string $name
 * @property string|null $position
 * @property string|null $email
 * @property string|null $phone
 */
final class SupplierContact extends Model
{
    protected $fillable = ['name', 'position', 'email', 'phone'];
}
