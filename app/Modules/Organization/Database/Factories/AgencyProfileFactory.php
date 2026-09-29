<?php

declare(strict_types=1);

namespace App\Modules\Organization\Database\Factories;

use App\Modules\Organization\Models\AgencyProfile;
use App\Modules\Organization\Services\NitCheckDigit;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AgencyProfile> */
final class AgencyProfileFactory extends Factory
{
    protected $model = AgencyProfile::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $nit = fake()->numerify('9########');

        return [
            'legal_name' => fake()->company() . ' S.A.S.',
            'trade_name' => fake()->company(),
            'nit' => $nit,
            'nit_check_digit' => NitCheckDigit::for($nit),
            'rnt_number' => fake()->numerify('#####'),
            'rnt_expires_on' => now()->addYear()->toDateString(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'phone' => fake()->numerify('60########'),
            'email' => fake()->companyEmail(),
            'website' => null,
            'logo_path' => null,
            'brand_primary_color' => null,
            'brand_accent_color' => null,
        ];
    }
}
