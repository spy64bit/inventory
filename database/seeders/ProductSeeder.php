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
        // Only assign products to leaf categories (children), not parent groups
        $categoryIds = Category::whereNotNull('parent_id')->pluck('id');
        $supplierIds = Supplier::pluck('id');

        Product::factory(20)->create([
            'category_id' => fn () => $categoryIds->random(),
            'supplier_id' => fn () => $supplierIds->random(),
        ]);
    }
}
