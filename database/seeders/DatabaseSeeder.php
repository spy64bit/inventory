<?php

namespace Database\Seeders;

use App\Enums\Position;
use App\Models\Product;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        // admin
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'position' => Position::Admin,
        ]);

        // manager
        User::factory()->create([
            'name' => 'Manager',
            'email' => 'manager@example.com',
            'password' => bcrypt('password'),
            'position' => Position::Manager,
        ]);

        // staff
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

        Product::factory(50)->create();
    }
}
