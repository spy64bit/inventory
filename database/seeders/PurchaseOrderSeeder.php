<?php

namespace Database\Seeders;

use App\Enums\Position;
use App\Enums\PurchaseOrderStatus;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
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
        $supplierIds = Supplier::pluck('id');
        $productIds = Product::pluck('id');

        $makeItems = function (int $count) use ($productIds): array {
            return $productIds->shuffle()->take($count)->values()->map(fn ($id) => [
                'product_id' => $id,
            ])->all();
        };

        // Draft POs
        PurchaseOrder::factory()
            ->has(PurchaseOrderItem::factory()->count(3)->sequence(...$makeItems(3)), 'items')
            ->create([
                'supplier_id' => $supplierIds->random(),
                'created_by' => $admin->id,
            ]);

        PurchaseOrder::factory()
            ->has(PurchaseOrderItem::factory()->count(2)->sequence(...$makeItems(2)), 'items')
            ->create([
                'supplier_id' => $supplierIds->random(),
                'created_by' => $manager->id,
            ]);

        // Approved PO
        PurchaseOrder::factory()
            ->approved()
            ->has(PurchaseOrderItem::factory()->count(2)->sequence(...$makeItems(2)), 'items')
            ->create([
                'supplier_id' => $supplierIds->random(),
                'created_by' => $admin->id,
                'approved_by' => $manager->id,
            ]);

        // Dispatched PO
        PurchaseOrder::factory()
            ->dispatched()
            ->has(PurchaseOrderItem::factory()->count(4)->sequence(...$makeItems(4)), 'items')
            ->create([
                'supplier_id' => $supplierIds->random(),
                'created_by' => $admin->id,
                'approved_by' => $manager->id,
            ]);

        // Received PO — goes through the service so stock movements are recorded
        $receivedPo = PurchaseOrder::factory()
            ->dispatched()
            ->has(PurchaseOrderItem::factory()->count(3)->sequence(...$makeItems(3)), 'items')
            ->create([
                'supplier_id' => $supplierIds->random(),
                'created_by' => $admin->id,
                'approved_by' => $manager->id,
            ]);

        $receivedPo->load('items');
        Auth::setUser($admin);

        $this->purchaseOrderService->receive(
            $receivedPo,
            $receivedPo->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'quantity_received' => $item->quantity_ordered,
            ])->toArray()
        );

        // Cancelled PO
        PurchaseOrder::factory()
            ->has(PurchaseOrderItem::factory()->count(2)->sequence(...$makeItems(2)), 'items')
            ->create([
                'supplier_id' => $supplierIds->random(),
                'created_by' => $manager->id,
                'status' => PurchaseOrderStatus::Cancelled,
            ]);
    }
}
