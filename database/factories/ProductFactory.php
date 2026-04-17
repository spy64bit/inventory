<?php

namespace Database\Factories;

use App\Models\Model;
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
        return [
            'sku' => $this->faker->unique()->bothify('PRD-####'),
            'name' => $this->faker->word(50),
            'description' => $this->faker->sentence(),
            'cost_price' => $this->faker->randomFloat(2, 1, 100),
            'reorder_level' => $this->faker->numberBetween(1, 50),
        ];
    }
}
