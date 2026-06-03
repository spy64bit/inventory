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
        $now = now();

        $suppliers = Supplier::pluck('id');
        $products = Product::pluck('id')->shuffle();

        // Helper to build PO items
        $makeItems = fn (array $productIds) => collect($productIds)->map(fn ($id, $index) => [
            'product_id' => $id,
            'quantity_ordered' => [10, 20, 50, 100, 25, 30, 15, 40][$index % 8],
            'quantity_received' => 0,
            'unit_cost' => [12.50, 25.00, 8.90, 45.00, 18.00, 33.50, 7.00, 60.00][$index % 8],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 1. Draft PO — Admin
        $draft1 = PurchaseOrder::create([
            'supplier_id' => $suppliers->random(),
            'status' => PurchaseOrderStatus::Draft,
            'created_by' => $admin->id,
            'notes' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        PurchaseOrderItem::insert(
            collect($makeItems($products->take(3)->all()))
                ->map(fn ($item) => array_merge($item, ['purchase_order_id' => $draft1->id]))
                ->all()
        );

        // 2. Draft PO — Manager
        $draft2 = PurchaseOrder::create([
            'supplier_id' => $suppliers->random(),
            'status' => PurchaseOrderStatus::Draft,
            'created_by' => $manager->id,
            'notes' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        PurchaseOrderItem::insert(
            collect($makeItems($products->slice(3, 2)->all()))
                ->map(fn ($item) => array_merge($item, ['purchase_order_id' => $draft2->id]))
                ->all()
        );

        // 3. Approved PO
        $approved = PurchaseOrder::create([
            'supplier_id' => $suppliers->random(),
            'status' => PurchaseOrderStatus::Approved,
            'created_by' => $admin->id,
            'approved_by' => $manager->id,
            'approved_at' => $now,
            'notes' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        PurchaseOrderItem::insert(
            collect($makeItems($products->slice(5, 2)->all()))
                ->map(fn ($item) => array_merge($item, ['purchase_order_id' => $approved->id]))
                ->all()
        );

        // 4. Dispatched PO
        $dispatched = PurchaseOrder::create([
            'supplier_id' => $suppliers->random(),
            'status' => PurchaseOrderStatus::Dispatched,
            'created_by' => $admin->id,
            'approved_by' => $manager->id,
            'approved_at' => $now,
            'dispatched_at' => $now,
            'notes' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        PurchaseOrderItem::insert(
            collect($makeItems($products->slice(7, 4)->all()))
                ->map(fn ($item) => array_merge($item, ['purchase_order_id' => $dispatched->id]))
                ->all()
        );

        // 5. Received PO — goes through service so stock movements are recorded
        $toReceive = PurchaseOrder::create([
            'supplier_id' => $suppliers->random(),
            'status' => PurchaseOrderStatus::Dispatched,
            'created_by' => $admin->id,
            'approved_by' => $manager->id,
            'approved_at' => $now,
            'dispatched_at' => $now,
            'notes' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        PurchaseOrderItem::insert(
            collect($makeItems($products->slice(11, 3)->all()))
                ->map(fn ($item) => array_merge($item, ['purchase_order_id' => $toReceive->id]))
                ->all()
        );

        $toReceive->load('items');
        Auth::setUser($admin);

        $this->purchaseOrderService->receive(
            $toReceive,
            $toReceive->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'quantity_received' => $item->quantity_ordered,
            ])->toArray()
        );

        // 6. Cancelled PO
        $cancelled = PurchaseOrder::create([
            'supplier_id' => $suppliers->random(),
            'status' => PurchaseOrderStatus::Cancelled,
            'created_by' => $manager->id,
            'notes' => 'Supplier unavailable.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        PurchaseOrderItem::insert(
            collect($makeItems($products->slice(14, 2)->all()))
                ->map(fn ($item) => array_merge($item, ['purchase_order_id' => $cancelled->id]))
                ->all()
        );
    }
}
