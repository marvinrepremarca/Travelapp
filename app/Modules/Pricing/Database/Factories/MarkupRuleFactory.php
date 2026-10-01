<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Database\Factories;

use App\Modules\Pricing\Enums\MarkupKind;
use App\Modules\Pricing\Models\MarkupRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MarkupRule> */
final class MarkupRuleFactory extends Factory
{
    protected $model = MarkupRule::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'kind' => MarkupKind::Percentage,
            'rate_basis_points' => 1000,
            'priority' => 0,
            'valid_from' => '2020-01-01',
            'is_active' => true,
        ];
    }

    public function percentage(int $basisPoints): self
    {
        return $this->state(['kind' => MarkupKind::Percentage, 'rate_basis_points' => $basisPoints, 'amount_minor' => null, 'currency' => null]);
    }

    public function fixed(MarkupKind $kind, int $amountMinor, string $currency): self
    {
        return $this->state(['kind' => $kind, 'rate_basis_points' => null, 'amount_minor' => $amountMinor, 'currency' => $currency]);
    }
}
