<?php

declare(strict_types=1);

namespace App\Modules\Workflow\Database\Factories;

use App\Modules\Identity\Models\User;
use App\Modules\Workflow\Enums\TaskPriority;
use App\Modules\Workflow\Enums\TaskStatus;
use App\Modules\Workflow\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Task> */
final class TaskFactory extends Factory
{
    protected $model = Task::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'status' => TaskStatus::Open,
            'priority' => TaskPriority::Normal,
            'due_at' => now()->addDays(2),
            'remind_at' => null,
            'owner_id' => User::factory(),
            'branch_id' => null,
            'created_by' => User::factory(),
        ];
    }

    public function assignedTo(User $user): self
    {
        return $this->state(['owner_id' => $user->id, 'branch_id' => $user->branch_id]);
    }

    public function done(): self
    {
        return $this->state(['status' => TaskStatus::Done, 'completed_at' => now()]);
    }

    public function overdue(): self
    {
        return $this->state(['due_at' => now()->subDay()]);
    }

    public function remindAt(\DateTimeInterface $when): self
    {
        return $this->state(['remind_at' => $when]);
    }
}
