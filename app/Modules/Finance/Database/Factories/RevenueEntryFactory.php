<?php

declare(strict_types=1);

namespace App\Modules\Finance\Database\Factories;

use App\Modules\Finance\Enums\RevenueEntryType;
use App\Modules\Finance\Enums\RevenueSource;
use App\Modules\Finance\Models\RevenueEntry;
use App\Modules\Identity\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RevenueEntry> */
final class RevenueEntryFactory extends Factory
{
    protected $model = RevenueEntry::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'source' => RevenueSource::Manual,
            'entry_type' => RevenueEntryType::Recognition,
            'description' => fake()->sentence(3),
            'customer_name' => fake()->name(),
            'amount_minor' => fake()->numberBetween(100_000, 5_000_000),
            'currency' => config()->string('travel.agency.default_currency'),
            'recognized_on' => CarbonImmutable::now()->toDateString(),
            'owner_id' => User::factory(),
            'branch_id' => null,
        ];
    }

    public function ownedBy(User $user): self
    {
        return $this->state(['owner_id' => $user->id, 'branch_id' => $user->branch_id]);
    }
}
