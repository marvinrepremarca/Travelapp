<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Database\Factories;

use App\Modules\Customers\Models\Customer;
use App\Modules\Identity\Models\User;
use App\Modules\Quotes\Enums\QuoteStatus;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Models\QuoteOption;
use App\Modules\Shared\Enums\SalesChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Quote> */
final class QuoteFactory extends Factory
{
    protected $model = Quote::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'owner_id' => User::factory(),
            'branch_id' => null,
            'title' => 'Viaje a ' . fake()->city(),
            'sale_currency' => config()->string('travel.agency.default_currency'),
            'sales_channel' => SalesChannel::Branch,
            'status' => QuoteStatus::Draft,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(static function (Quote $quote): void {
            QuoteOption::query()->create(['quote_id' => $quote->id, 'label' => QuoteOption::FIRST_LABEL, 'title' => $quote->title]);
        });
    }

    public function ownedBy(User $user): self
    {
        return $this->state(['owner_id' => $user->id, 'branch_id' => $user->branch_id]);
    }
}
