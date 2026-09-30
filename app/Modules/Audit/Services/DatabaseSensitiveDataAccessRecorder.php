<?php

declare(strict_types=1);

namespace App\Modules\Audit\Services;

use App\Modules\Audit\Contracts\SensitiveDataAccessRecorder;
use App\Modules\Audit\Enums\SensitiveDataAccessType;
use App\Modules\Audit\Models\SensitiveDataAccess;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

final readonly class DatabaseSensitiveDataAccessRecorder implements SensitiveDataAccessRecorder
{
    public function __construct(private Request $request) {}

    public function record(Authenticatable $viewer, Model $subject, string $field, SensitiveDataAccessType $type, ?string $reason = null): void
    {
        SensitiveDataAccess::query()->create([
            'user_id' => $viewer->getAuthIdentifier(),
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => (string) $subject->getKey(),
            'field' => $field,
            'type' => $type,
            'reason' => $reason,
            'ip_address' => $this->request->ip(),
            'accessed_at' => CarbonImmutable::now(),
        ]);
    }
}
