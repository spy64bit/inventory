<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Customer::firstOrCreate(
            [
                'name' => 'Walk-in/ Cash Sale Customer',
                'email' => 'walkin@example.com',
                'contact_no' => null,
                'address' => null,
                'is_system' => true,
            ],
        );
    }
}
