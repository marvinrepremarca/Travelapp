<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Versión enviada al cliente: copia inmutable de opciones, ítems y precios. Nunca se edita ni se borra.
 *
 * @property int $id
 * @property int $quote_id
 * @property int $version
 * @property array{options: list<array{ulid: string, label: string, title: string, sale_total_minor: int, items: list<array<string, mixed>>}>, currency: string} $snapshot
 * @property CarbonImmutable $sent_at
 * @property CarbonImmutable $valid_until
 * @property int $sent_by
 */
final class QuoteVersion extends Model
{
    protected $fillable = ['quote_id', 'version', 'snapshot', 'sent_at', 'valid_until', 'sent_by'];

    protected static function booted(): void
    {
        self::updating(static fn(): never => throw new LogicException('Las versiones enviadas son inmutables.'));
        self::deleting(static fn(): never => throw new LogicException('Las versiones enviadas son inmutables.'));
    }

    /** @return BelongsTo<Quote, $this> */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'snapshot' => 'array',
            'sent_at' => 'immutable_datetime',
            'valid_until' => 'immutable_datetime',
        ];
    }
}
