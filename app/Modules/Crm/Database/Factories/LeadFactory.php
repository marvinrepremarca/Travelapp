<?php

declare(strict_types=1);

namespace App\Modules\Crm\Database\Factories;

use App\Modules\Crm\Enums\LeadStatus;
use App\Modules\Crm\Models\Lead;
use App\Modules\Identity\Models\User;
use App\Modules\Shared\Enums\SalesChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Lead> */
final class LeadFactory extends Factory
{
    protected $model = Lead::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'contact_name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->numerify('3#########'),
            'channel' => SalesChannel::WhatsApp,
            'destination' => fake()->city(),
            'travelers_count' => 2,
            'status' => LeadStatus::New,
            'owner_id' => User::factory(),
            'branch_id' => null,
        ];
    }

    public function ownedBy(User $user): self
    {
        return $this->state(['owner_id' => $user->id, 'branch_id' => $user->branch_id]);
    }

    public function inStatus(LeadStatus $status): self
    {
        return $this->state(['status' => $status]);
    }
}
