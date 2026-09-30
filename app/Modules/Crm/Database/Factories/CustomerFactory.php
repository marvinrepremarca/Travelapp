<?php

declare(strict_types=1);

namespace App\Modules\Crm\Database\Factories;

use App\Modules\Crm\Enums\CustomerType;
use App\Modules\Crm\Enums\DocumentType;
use App\Modules\Crm\Models\Customer;
use App\Modules\Crm\Services\CustomerDocumentGuard;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Customer> */
final class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $first = fake()->firstName();
        $last = fake()->lastName();

        return [
            'type' => CustomerType::Person,
            'first_name' => $first,
            'last_name' => $last,
            'display_name' => "{$first} {$last}",
            'document_type' => DocumentType::CitizenId,
            'document_number' => fake()->unique()->numerify('##########'),
            'birth_date' => fake()->dateTimeBetween('-70 years', '-18 years')->format('Y-m-d'),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('3#########'),
            'city' => fake()->city(),
            'country' => 'CO',
            'owner_id' => User::factory(),
            'branch_id' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(static function (Customer $customer): void {
            $customer->document_hash = app(CustomerDocumentGuard::class)
                ->hashFor($customer->document_type->value, $customer->document_type->normalize($customer->document_number));
        });
    }

    public function ownedBy(User $user): self
    {
        return $this->state(['owner_id' => $user->id, 'branch_id' => $user->branch_id]);
    }

    public function company(): self
    {
        $name = fake()->company();

        return $this->state([
            'type' => CustomerType::Company,
            'first_name' => null,
            'last_name' => null,
            'legal_name' => $name,
            'display_name' => $name,
            'document_type' => DocumentType::Nit,
            'birth_date' => null,
        ]);
    }
}
