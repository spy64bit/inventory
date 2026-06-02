<?php

namespace Database\Factories;

use App\Enums\SalesOrderStatus;
use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesOrder>
 */
class SalesOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::inRandomOrder()->first()?->id,
            'status' => SalesOrderStatus::Draft,
            'created_by' => User::inRandomOrder()->first()?->id,
            'confirmed_at' => null,
            'fulfilled_at' => null,
            'notes' => $this->faker->optional(0.3)->sentence(),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => [
            'status' => SalesOrderStatus::Confirmed,
            'confirmed_at' => now()->subDays(rand(1, 5)),
        ]);
    }

    public function fulfilled(): static
    {
        return $this->state(fn () => [
            'status' => SalesOrderStatus::Fulfilled,
            'confirmed_at' => now()->subDays(rand(5, 10)),
            'fulfilled_at' => now()->subDays(rand(1, 4)),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => SalesOrderStatus::Cancelled,
        ]);
    }
}
