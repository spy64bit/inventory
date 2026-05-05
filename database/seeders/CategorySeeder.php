<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Electronics' => ['Phones', 'Laptops', 'Accessories'],
            'Food & Beverage' => ['Dry Goods', 'Beverages', 'Snacks'],
            'Office Supplies' => ['Stationery', 'Furniture', 'Equipment'],
            'Clothing' => ['Men', 'Women', 'Kids'],
        ];

        foreach ($categories as $parent => $children) {
            $parentCategory = Category::create([
                'name' => $parent,
                'slug' => Str::slug($parent),
            ]);

            foreach ($children as $child) {
                Category::create([
                    'name' => $child,
                    'slug' => Str::slug($parent.'-'.$child),
                    'parent_id' => $parentCategory->id,
                ]);
            }
        }
    }
}
