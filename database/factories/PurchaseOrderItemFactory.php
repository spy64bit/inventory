<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrderItem>
 */
class PurchaseOrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantityOrdered = $this->faker->numberBetween(1, 100);

        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'product_id' => Product::factory(),
            'quantity_ordered' => $quantityOrdered,
            'quantity_received' => 0,
            'unit_cost' => $this->faker->randomFloat(2, 10, 1000),
        ];
    }

    public function fullyReceived(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity_received' => $attributes['quantity_ordered'],
        ]);
    }

    public function partiallyReceived(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity_received' => (int) ($attributes['quantity_ordered'] / 2),
        ]);
    }
}
