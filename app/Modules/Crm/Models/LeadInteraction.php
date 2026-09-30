<?php

declare(strict_types=1);

namespace App\Modules\Crm\Models;

use App\Modules\Crm\Enums\InteractionType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $lead_id
 * @property InteractionType $type
 * @property string $summary
 * @property CarbonImmutable $occurred_at
 * @property int $user_id
 */
final class LeadInteraction extends Model
{
    protected $fillable = ['type', 'summary', 'occurred_at', 'user_id'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => InteractionType::class,
            'occurred_at' => 'immutable_datetime',
        ];
    }
}
