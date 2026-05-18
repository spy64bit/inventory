<?php

namespace Database\Factories;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'status' => PurchaseOrderStatus::Draft,
            'created_by' => User::factory(),
            'approved_by' => null,
            'approved_at' => null,
            'dispatched_at' => null,
            'received_at' => null,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => PurchaseOrderStatus::Approved,
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ]);
    }

    public function dispatched(): static
    {
        return $this->state(fn () => [
            'status' => PurchaseOrderStatus::Dispatched,
            'approved_by' => User::factory(),
            'approved_at' => now()->subDays(2),
            'dispatched_at' => now(),
        ]);
    }

    public function received(): static
    {
        return $this->state(fn () => [
            'status' => PurchaseOrderStatus::Received,
            'approved_by' => User::factory(),
            'approved_at' => now()->subDays(3),
            'dispatched_at' => now()->subDays(2),
            'received_at' => now(),
        ]);
    }
}
