<?php

declare(strict_types=1);

namespace App\Modules\Customers\Database\Factories;

use App\Modules\Customers\Enums\Gender;
use App\Modules\Customers\Models\Customer;
use App\Modules\Customers\Models\Traveler;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Traveler> */
final class TravelerFactory extends Factory
{
    protected $model = Traveler::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'gender' => fake()->randomElement(Gender::cases()),
            'birth_date' => fake()->dateTimeBetween('-60 years', '-20 years')->format('Y-m-d'),
            'nationality' => 'CO',
            'passport_country' => 'CO',
            'passport_expires_on' => now()->addYears(5)->toDateString(),
        ];
    }

    public function child(int $age): self
    {
        return $this->state(['birth_date' => now()->subYears($age)->subDay()->toDateString()]);
    }
}
