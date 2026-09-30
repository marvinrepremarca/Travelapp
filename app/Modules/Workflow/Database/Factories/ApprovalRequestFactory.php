<?php

declare(strict_types=1);

namespace App\Modules\Workflow\Database\Factories;

use App\Modules\Identity\Models\User;
use App\Modules\Workflow\Enums\ApprovalStatus;
use App\Modules\Workflow\Enums\ApprovalType;
use App\Modules\Workflow\Models\ApprovalRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ApprovalRequest> */
final class ApprovalRequestFactory extends Factory
{
    protected $model = ApprovalRequest::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'type' => ApprovalType::Discount,
            'status' => ApprovalStatus::Pending,
            'subject_type' => 'booking',
            'subject_id' => (string) fake()->randomNumber(5),
            'summary' => fake()->sentence(),
            'justification' => fake()->sentence(),
            'owner_id' => User::factory(),
            'branch_id' => null,
        ];
    }

    public function requestedBy(User $user): self
    {
        return $this->state(['owner_id' => $user->id, 'branch_id' => $user->branch_id]);
    }

    public function ofType(ApprovalType $type): self
    {
        return $this->state(['type' => $type]);
    }
}
