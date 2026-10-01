<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Database\Factories;

use App\Modules\Catalog\Models\CatalogProduct;
use App\Modules\Shared\Enums\ProductType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CatalogProduct> */
final class CatalogProductFactory extends Factory
{
    private const DEFAULT_DURATION_MINUTES = 240;

    protected $model = CatalogProduct::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('TOUR-####'),
            'name' => 'Tour ' . fake()->city(),
            'product_type' => ProductType::Tour,
            'destination_country' => 'CO',
            'destination_city' => 'Cartagena',
            'timezone' => config()->string('travel.agency.timezone'),
            'duration_minutes' => self::DEFAULT_DURATION_MINUTES,
            'currency' => config()->string('travel.agency.default_currency'),
            'is_active' => true,
        ];
    }

    public function inactive(): self
    {
        return $this->state(['is_active' => false]);
    }
}
