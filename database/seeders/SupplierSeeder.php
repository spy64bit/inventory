<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'Apex Distribution Co.',
                'email' => 'orders@apexdist.com',
                'phone' => '+1-212-555-0181',
                'address' => '300 Commerce Blvd, Newark, NJ 07102, USA',
                'lead_time_days' => 3,
            ],
            [
                'name' => 'Meridian Wholesale Ltd.',
                'email' => 'sales@meridianwholesale.com',
                'phone' => '+44-20-7946-0392',
                'address' => '14 Trade Park Road, Birmingham, B7 4AA, UK',
                'lead_time_days' => 5,
            ],
            [
                'name' => 'Harbour Supplies Group',
                'email' => 'procurement@harboursupplies.com',
                'phone' => '+61-2-9374-5500',
                'address' => 'Unit 8, 22 Industrial Ave, Homebush NSW 2140, Australia',
                'lead_time_days' => 7,
            ],
            [
                'name' => 'Pinnacle Trading Inc.',
                'email' => 'contact@pinnacletrading.com',
                'phone' => '+1-416-555-0234',
                'address' => '980 Logistics Drive, Mississauga, ON L5T 2J9, Canada',
                'lead_time_days' => 10,
            ],
            [
                'name' => 'Crestline Office & FMCG Supply',
                'email' => 'info@crestlinesupply.com',
                'phone' => '+65-6438-7700',
                'address' => '18 Tuas South Street 5, Singapore 637051',
                'lead_time_days' => 2,
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}
