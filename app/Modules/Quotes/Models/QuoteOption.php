<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Models;

use App\Modules\Shared\Enums\AuditLogName;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Alternativa dentro de una cotización (A, B, C); el cliente acepta una.
 *
 * @property int $id
 * @property string $ulid
 * @property int $quote_id
 * @property string $label
 * @property string $title
 * @property-read \Illuminate\Database\Eloquent\Collection<int, QuoteItem> $items
 */
final class QuoteOption extends Model
{
    use HasUlids;
    use LogsActivity;

    /** Etiqueta de la primera opción; las siguientes siguen el alfabeto (B, C…). */
    public const FIRST_LABEL = 'A';

    protected $fillable = ['quote_id', 'label', 'title'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /** @return BelongsTo<Quote, $this> */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /** @return HasMany<QuoteItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class, 'option_id')->orderBy('service_date')->orderBy('id');
    }

    public function saleTotal(string $currency): Money
    {
        return $this->items->reduce(static fn(Money $carry, QuoteItem $item): Money => $carry->plus($item->saleAmount()), Money::zero($currency));
    }

    public function marginTotal(string $currency): Money
    {
        return $this->items->reduce(static fn(Money $carry, QuoteItem $item): Money => $carry->plus($item->marginAmount()), Money::zero($currency));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->useLogName(AuditLogName::Quotes->value);
    }
}
