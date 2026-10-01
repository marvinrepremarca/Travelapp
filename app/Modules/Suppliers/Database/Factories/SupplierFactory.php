<?php

declare(strict_types=1);

namespace App\Modules\Suppliers\Database\Factories;

use App\Modules\Suppliers\Enums\PaymentTerms;
use App\Modules\Suppliers\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Supplier> */
final class SupplierFactory extends Factory
{
    private const DEFAULT_PAYMENT_DAYS = 15;

    protected $model = Supplier::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'legal_name' => $name . ' S.A.S.',
            'trade_name' => $name,
            'tax_id' => fake()->unique()->numerify('9########'),
            'country' => Supplier::RNT_COUNTRY,
            'is_tourism_provider' => true,
            'rnt_number' => fake()->numerify('#####'),
            'rnt_expires_on' => now()->addYear()->toDateString(),
            'email' => fake()->companyEmail(),
            'phone' => fake()->numerify('60########'),
            'payment_terms' => PaymentTerms::Credit,
            'payment_days' => self::DEFAULT_PAYMENT_DAYS,
            'payment_currency' => 'COP',
            'is_active' => true,
        ];
    }

    public function foreign(): self
    {
        return $this->state(['country' => 'US', 'rnt_number' => null, 'rnt_expires_on' => null, 'payment_currency' => 'USD']);
    }

    public function rntExpiringIn(int $days): self
    {
        return $this->state(['rnt_expires_on' => now()->addDays($days)->toDateString()]);
    }

    public function inactive(): self
    {
        return $this->state(['is_active' => false]);
    }
}
