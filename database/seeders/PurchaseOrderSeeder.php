<?php

namespace Database\Seeders;

use App\Enums\Position;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class PurchaseOrderSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('position', Position::Admin)->first();
        $manager = User::where('position', Position::Manager)->first();

        // Draft PO
        PurchaseOrder::factory()
            ->has(PurchaseOrderItem::factory()->count(3), 'items')
            ->create(['created_by' => $admin->id]);

        // Approved PO
        PurchaseOrder::factory()
            ->approved()
            ->has(PurchaseOrderItem::factory()->count(2), 'items')
            ->create([
                'created_by' => $admin->id,
                'approved_by' => $manager->id,
            ]);

        // Dispatched PO
        PurchaseOrder::factory()
            ->dispatched()
            ->has(PurchaseOrderItem::factory()->count(4), 'items')
            ->create([
                'created_by' => $admin->id,
                'approved_by' => $manager->id,
            ]);

        // Received PO
        PurchaseOrder::factory()
            ->received()
            ->has(PurchaseOrderItem::factory()->fullyReceived()->count(3), 'items')
            ->create([
                'created_by' => $admin->id,
                'approved_by' => $manager->id,
            ]);
    }
}
