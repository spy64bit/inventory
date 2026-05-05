<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'Tech Distributors Sdn Bhd',
                'email' => 'sales@techdist.com.my',
                'phone' => '03-12345678',
                'address' => 'Kuala Lumpur, Malaysia',
                'lead_time_days' => 3,
            ],
            [
                'name' => 'Global Supply Co',
                'email' => 'orders@globalsupply.com',
                'phone' => '03-87654321',
                'address' => 'Petaling Jaya, Selangor',
                'lead_time_days' => 7,
            ],
            [
                'name' => 'FastStock Trading',
                'email' => 'info@faststock.com.my',
                'phone' => '04-11223344',
                'address' => 'George Town, Penang',
                'lead_time_days' => 2,
            ],
            [
                'name' => 'Prime Wholesale Bhd',
                'email' => 'wholesale@primebhd.com.my',
                'phone' => '07-99887766',
                'address' => 'Johor Bahru, Johor',
                'lead_time_days' => 14,
            ],
            [
                'name' => 'Evergreen Supplies',
                'email' => 'contact@evergreen.com.my',
                'phone' => '088-556677',
                'address' => 'Kota Kinabalu, Sabah',
                'lead_time_days' => 10,
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}
