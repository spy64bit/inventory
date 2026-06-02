<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    private static array $products = [
        // Beverages
        ['name' => 'Mineral Water 500ml (24-pack)',       'uom' => 'carton'],
        ['name' => 'Sparkling Water 330ml (24-pack)',     'uom' => 'carton'],
        ['name' => 'Orange Juice 1L',                    'uom' => 'piece'],
        ['name' => 'Apple Juice 1L',                     'uom' => 'piece'],
        ['name' => 'Green Tea 350ml (24-pack)',           'uom' => 'carton'],
        ['name' => 'Black Coffee RTD 250ml (24-pack)',    'uom' => 'carton'],
        ['name' => 'Instant Coffee 3-in-1 (30 sachets)', 'uom' => 'box'],
        ['name' => 'Earl Grey Tea Bags (100-pack)',       'uom' => 'box'],

        // Snacks & Food
        ['name' => 'Butter Crackers 200g',               'uom' => 'piece'],
        ['name' => 'Digestive Biscuits 400g',            'uom' => 'piece'],
        ['name' => 'Instant Noodles Chicken (5-pack)',   'uom' => 'piece'],
        ['name' => 'Instant Noodles Curry (5-pack)',     'uom' => 'piece'],
        ['name' => 'Canned Tuna in Springwater 185g',    'uom' => 'piece'],
        ['name' => 'Canned Sweet Corn 400g',             'uom' => 'piece'],
        ['name' => 'Mixed Nuts 500g',                    'uom' => 'piece'],
        ['name' => 'Dark Chocolate Bar 100g',            'uom' => 'piece'],

        // Office Supplies
        ['name' => 'A4 Copy Paper 80gsm (500 sheets)',   'uom' => 'ream'],
        ['name' => 'Ballpoint Pen Blue (12-pack)',        'uom' => 'box'],
        ['name' => 'Permanent Marker Black (10-pack)',    'uom' => 'box'],
        ['name' => 'Sticky Notes 76x76mm (12-pack)',     'uom' => 'pack'],
        ['name' => 'Lever Arch File A4',                 'uom' => 'piece'],
        ['name' => 'Clear Document Folder A4 (10-pack)', 'uom' => 'pack'],
        ['name' => 'Stapler Heavy Duty',                 'uom' => 'piece'],
        ['name' => 'Staple Refill 26/6 (1000-pack)',     'uom' => 'box'],

        // Cleaning
        ['name' => 'Dishwashing Liquid 1L',              'uom' => 'piece'],
        ['name' => 'All-Purpose Cleaner Spray 750ml',    'uom' => 'piece'],
        ['name' => 'Disinfectant Floor Cleaner 2L',      'uom' => 'piece'],
        ['name' => 'Hand Soap Refill 1L',                'uom' => 'piece'],
        ['name' => 'Bin Liner 60L (50-pack)',             'uom' => 'pack'],
        ['name' => 'Paper Towel Roll (6-pack)',           'uom' => 'pack'],
    ];

    public function definition(): array
    {
        $product = $this->faker->unique()->randomElement(self::$products);
        $costPrice = $this->faker->randomFloat(2, 5, 300);

        return [
            'sku' => 'PRD-'.strtoupper($this->faker->unique()->bothify('??-####')),
            'name' => $product['name'],
            'description' => null,
            'cost_price' => $costPrice,
            'selling_price' => round($costPrice * $this->faker->randomFloat(2, 1.15, 2.0), 2),
            'unit_of_measure' => $product['uom'],
            'current_stock' => 0, // always start at 0; let PurchaseOrderSeeder build stock naturally
            'reorder_level' => $this->faker->randomElement([5, 10, 20, 50]),
            'category_id' => Category::factory(),
            'supplier_id' => Supplier::factory(),
        ];
    }
}
