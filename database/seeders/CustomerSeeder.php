<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        // System customer — walk-in / cash sales, always present
        Customer::firstOrCreate(
            ['email' => 'walkin@internal.local'],
            [
                'name' => 'Walk-in / Cash Sale',
                'contact_no' => null,
                'address' => null,
                'is_system' => true,
            ]
        );

        $customers = [
            [
                'name' => 'Greenfield Grocery Store',
                'email' => 'purchasing@greenfielgrocery.com',
                'contact_no' => '+1-305-555-0192',
                'address' => '88 Market Street, Miami, FL 33101, USA',
                'is_system' => false,
            ],
            [
                'name' => 'Horizon Mini Mart',
                'email' => 'orders@horizonminimart.com',
                'contact_no' => '+44-161-555-0344',
                'address' => '22 High Street, Manchester, M1 2HX, UK',
                'is_system' => false,
            ],
            [
                'name' => 'Crestview Office Solutions',
                'email' => 'admin@crestviewoffice.com',
                'contact_no' => '+61-3-9555-0412',
                'address' => 'Level 2, 55 Collins Street, Melbourne VIC 3000, Australia',
                'is_system' => false,
            ],
            [
                'name' => 'Sunrise Convenience Chain',
                'email' => 'supply@sunriseconvenience.com',
                'contact_no' => '+1-604-555-0287',
                'address' => '400 Robson Street, Vancouver, BC V6B 2B3, Canada',
                'is_system' => false,
            ],
            [
                'name' => 'Blue Harbor Café & Catering',
                'email' => 'inventory@blueharborcafe.com',
                'contact_no' => '+65-9123-4567',
                'address' => '12 Boat Quay, Singapore 049817',
                'is_system' => false,
            ],
        ];

        foreach ($customers as $customer) {
            Customer::firstOrCreate(['email' => $customer['email']], $customer);
        }
    }
}
