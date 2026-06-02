<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Beverages' => ['Mineral Water', 'Soft Drinks', 'Coffee & Tea'],
            'Snacks & Food' => ['Biscuits', 'Instant Noodles', 'Canned Goods'],
            'Office Supplies' => ['Paper & Notebooks', 'Pens & Markers', 'Files & Folders'],
            'Cleaning' => ['Detergents', 'Disinfectants', 'Bin Liners'],
        ];

        foreach ($categories as $parent => $children) {
            $parentCategory = Category::create([
                'name' => $parent,
                'slug' => Str::slug($parent),
                'parent_id' => null,
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
