<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categoryIds = Category::whereNotNull('parent_id')->pluck('id');
        $supplierIds = Supplier::pluck('id');

        $products = [
            // Beverages
            ['name' => 'Mineral Water 500ml (24-pack)',         'uom' => 'carton',  'cost' => 18.00,  'sell' => 25.00,  'reorder' => 20],
            ['name' => 'Sparkling Water 330ml (24-pack)',       'uom' => 'carton',  'cost' => 22.00,  'sell' => 30.00,  'reorder' => 10],
            ['name' => 'Orange Juice 1L',                      'uom' => 'piece',   'cost' => 5.50,   'sell' => 8.90,   'reorder' => 20],
            ['name' => 'Apple Juice 1L',                       'uom' => 'piece',   'cost' => 5.50,   'sell' => 8.90,   'reorder' => 20],
            ['name' => 'Green Tea 350ml (24-pack)',             'uom' => 'carton',  'cost' => 20.00,  'sell' => 28.00,  'reorder' => 10],
            ['name' => 'Black Coffee RTD 250ml (24-pack)',      'uom' => 'carton',  'cost' => 24.00,  'sell' => 34.00,  'reorder' => 10],
            ['name' => 'Instant Coffee 3-in-1 (30 sachets)',   'uom' => 'box',     'cost' => 12.00,  'sell' => 18.00,  'reorder' => 10],
            ['name' => 'Earl Grey Tea Bags (100-pack)',         'uom' => 'box',     'cost' => 9.00,   'sell' => 14.00,  'reorder' => 10],
            // Snacks & Food
            ['name' => 'Butter Crackers 200g',                 'uom' => 'piece',   'cost' => 3.50,   'sell' => 5.90,   'reorder' => 20],
            ['name' => 'Digestive Biscuits 400g',              'uom' => 'piece',   'cost' => 4.50,   'sell' => 7.50,   'reorder' => 20],
            ['name' => 'Instant Noodles Chicken (5-pack)',     'uom' => 'piece',   'cost' => 4.00,   'sell' => 6.50,   'reorder' => 50],
            ['name' => 'Instant Noodles Curry (5-pack)',       'uom' => 'piece',   'cost' => 4.00,   'sell' => 6.50,   'reorder' => 50],
            ['name' => 'Canned Tuna in Springwater 185g',      'uom' => 'piece',   'cost' => 3.80,   'sell' => 6.00,   'reorder' => 20],
            ['name' => 'Canned Sweet Corn 400g',               'uom' => 'piece',   'cost' => 3.20,   'sell' => 5.50,   'reorder' => 20],
            ['name' => 'Mixed Nuts 500g',                      'uom' => 'piece',   'cost' => 18.00,  'sell' => 28.00,  'reorder' => 10],
            ['name' => 'Dark Chocolate Bar 100g',              'uom' => 'piece',   'cost' => 5.00,   'sell' => 8.50,   'reorder' => 10],
            // Office Supplies
            ['name' => 'A4 Copy Paper 80gsm (500 sheets)',     'uom' => 'ream',    'cost' => 12.00,  'sell' => 18.00,  'reorder' => 20],
            ['name' => 'Ballpoint Pen Blue (12-pack)',          'uom' => 'box',     'cost' => 5.00,   'sell' => 8.00,   'reorder' => 10],
            ['name' => 'Permanent Marker Black (10-pack)',      'uom' => 'box',     'cost' => 8.00,   'sell' => 13.00,  'reorder' => 10],
            ['name' => 'Sticky Notes 76x76mm (12-pack)',       'uom' => 'pack',    'cost' => 7.00,   'sell' => 11.00,  'reorder' => 10],
            ['name' => 'Lever Arch File A4',                   'uom' => 'piece',   'cost' => 6.00,   'sell' => 10.00,  'reorder' => 10],
            ['name' => 'Clear Document Folder A4 (10-pack)',   'uom' => 'pack',    'cost' => 4.50,   'sell' => 7.50,   'reorder' => 10],
            ['name' => 'Stapler Heavy Duty',                   'uom' => 'piece',   'cost' => 15.00,  'sell' => 25.00,  'reorder' => 5],
            ['name' => 'Staple Refill 26/6 (1000-pack)',       'uom' => 'box',     'cost' => 3.00,   'sell' => 5.00,   'reorder' => 10],
            // Cleaning
            ['name' => 'Dishwashing Liquid 1L',                'uom' => 'piece',   'cost' => 5.00,   'sell' => 8.00,   'reorder' => 10],
            ['name' => 'All-Purpose Cleaner Spray 750ml',      'uom' => 'piece',   'cost' => 6.00,   'sell' => 10.00,  'reorder' => 10],
            ['name' => 'Disinfectant Floor Cleaner 2L',        'uom' => 'piece',   'cost' => 9.00,   'sell' => 15.00,  'reorder' => 10],
            ['name' => 'Hand Soap Refill 1L',                  'uom' => 'piece',   'cost' => 7.00,   'sell' => 12.00,  'reorder' => 10],
            ['name' => 'Bin Liner 60L (50-pack)',               'uom' => 'pack',    'cost' => 8.00,   'sell' => 13.00,  'reorder' => 10],
            ['name' => 'Paper Towel Roll (6-pack)',             'uom' => 'pack',    'cost' => 10.00,  'sell' => 16.00,  'reorder' => 10],
        ];

        $now = now();
        $rows = [];

        foreach ($products as $index => $product) {
            $rows[] = [
                'sku' => 'PRD-'.str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                'name' => $product['name'],
                'description' => null,
                'cost_price' => $product['cost'],
                'selling_price' => $product['sell'],
                'unit_of_measure' => $product['uom'],
                'current_stock' => 0,
                'reorder_level' => $product['reorder'],
                'category_id' => $categoryIds->random(),
                'supplier_id' => $supplierIds->random(),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        Product::insert($rows);
    }
}
