<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $costPrice = $this->faker->randomFloat(2, 5, 500);

        return [
            'sku' => 'PRD-'.strtoupper($this->faker->unique()->bothify('??-####')),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'cost_price' => $costPrice,
            'selling_price' => round($costPrice * $this->faker->randomFloat(2, 1.1, 2.5), 2),
            'unit_of_measure' => $this->faker->randomElement(['piece', 'kg', 'liter', 'carton', 'box']),
            'current_stock' => $this->faker->randomFloat(2, 0, 500),
            'reorder_level' => $this->faker->randomFloat(2, 5, 50),
            'category_id' => Category::factory(),
            'supplier_id' => Supplier::factory(),
        ];
    }
}
