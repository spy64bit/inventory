<?php

namespace Database\Seeders;

use App\Enums\Position;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use App\Services\PurchaseOrderService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderSeeder extends Seeder
{
    public function __construct(
        private PurchaseOrderService $purchaseOrderService
    ) {}

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

        // Received PO — start as Dispatched so the service can transition it
        $receivedPo = PurchaseOrder::factory()
            ->dispatched()
            ->has(PurchaseOrderItem::factory()->count(3), 'items')
            ->create([
                'created_by' => $admin->id,
                'approved_by' => $manager->id,
            ]);

        $receivedPo->load('items');

        // Set the authenticated user so stock movements get a proper user_id
        Auth::setUser($admin);

        $this->purchaseOrderService->receive($receivedPo, $receivedPo->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'quantity_received' => $item->quantity_ordered,
        ])->toArray());
    }
}
