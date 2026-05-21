<?php

namespace Database\Seeders;

use App\Enums\Position;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'position' => Position::Admin,
        ]);

        User::factory()->create([
            'name' => 'Manager',
            'email' => 'manager@example.com',
            'password' => bcrypt('password'),
            'position' => Position::Manager,
        ]);

        User::factory()->create([
            'name' => 'Staff',
            'email' => 'staff@example.com',
            'password' => bcrypt('password'),
            'position' => Position::Staff,
        ]);

        $this->call([
            CategorySeeder::class,
            SupplierSeeder::class,
        ]);

        $categoryIds = Category::pluck('id');
        $supplierIds = Supplier::pluck('id');

        Product::factory(20)->create([
            'category_id' => fn () => $categoryIds->random(),
            'supplier_id' => fn () => $supplierIds->random(),
        ]);

        $this->call([
            PurchaseOrderSeeder::class,
        ]);
    }
}
