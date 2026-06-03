<?php

namespace Database\Seeders;

use App\Enums\Position;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::insert([
            [
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => bcrypt('password'),
                'position' => Position::Admin,
                'email_verified_at' => now(),
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Manager',
                'email' => 'manager@example.com',
                'password' => bcrypt('password'),
                'position' => Position::Manager,
                'email_verified_at' => now(),
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Staff',
                'email' => 'staff@example.com',
                'password' => bcrypt('password'),
                'position' => Position::Staff,
                'email_verified_at' => now(),
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->call([
            CategorySeeder::class,
            SupplierSeeder::class,
            CustomerSeeder::class,
            ProductSeeder::class,       // depends on categories + suppliers
            PurchaseOrderSeeder::class, // depends on products
            SalesOrderSeeder::class,    // depends on products + customers
        ]);
    }
}
