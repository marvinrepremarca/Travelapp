<?php

declare(strict_types=1);

namespace App\Modules\Suppliers\Models;

use App\Modules\Shared\Enums\AuditLogName;
use App\Modules\Shared\Support\Mask;
use App\Modules\Suppliers\Enums\BankAccountType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Cuenta bancaria del proveedor. El número se cifra en reposo y se enmascara en pantalla.
 *
 * @property int $id
 * @property string $ulid
 * @property int $supplier_id
 * @property string $bank_name
 * @property BankAccountType $account_type
 * @property string $account_number
 * @property string $holder_name
 * @property string $currency
 */
final class SupplierBankAccount extends Model
{
    use HasUlids;
    use LogsActivity;

    protected $fillable = ['bank_name', 'account_type', 'account_number', 'holder_name', 'currency'];

    protected $hidden = ['account_number'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function maskedNumber(): string
    {
        return Mask::value($this->account_number);
    }

    /** Se audita el cambio de cuenta, nunca el número. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['bank_name', 'account_type', 'holder_name', 'currency'])
            ->logOnlyDirty()
            ->useLogName(AuditLogName::Suppliers->value);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'account_type' => BankAccountType::class,
            'account_number' => 'encrypted',
        ];
    }
}
