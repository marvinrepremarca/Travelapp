<?php

declare(strict_types=1);

namespace App\Modules\Organization\Database\Factories;

use App\Modules\Organization\Enums\HolidayAdjustmentType;
use App\Modules\Organization\Models\HolidayAdjustment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<HolidayAdjustment> */
final class HolidayAdjustmentFactory extends Factory
{
    protected $model = HolidayAdjustment::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'date' => fake()->unique()->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
            'type' => HolidayAdjustmentType::Add,
            'name' => fake()->sentence(3),
        ];
    }

    public function removing(string $date): self
    {
        return $this->state(['date' => $date, 'type' => HolidayAdjustmentType::Remove]);
    }

    public function adding(string $date): self
    {
        return $this->state(['date' => $date, 'type' => HolidayAdjustmentType::Add]);
    }
}
