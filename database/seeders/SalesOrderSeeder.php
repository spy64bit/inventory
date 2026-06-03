<?php

namespace Database\Seeders;

use App\Enums\Position;
use App\Enums\SalesOrderStatus;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class SalesOrderSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('position', Position::Admin)->first();
        $manager = User::where('position', Position::Manager)->first();
        $staff = User::where('position', Position::Staff)->first();

        $customers = Customer::where('is_system', false)->pluck('id');
        $walkin = Customer::where('is_system', true)->first();
        $products = Product::pluck('id')->shuffle();

        $now = now();

        $makeItems = fn (array $productIds) => collect($productIds)->map(fn ($id, $index) => [
            'product_id' => $id,
            'quantity' => [1, 2, 5, 3, 10, 4, 2, 6][$index % 8],
            'unit_price' => [8.90, 25.00, 6.50, 18.00, 5.90, 30.00, 12.00, 45.00][$index % 8],
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        $insert = function (SalesOrder $so, array $productIds) use ($makeItems) {
            SalesOrderItem::insert(
                collect($makeItems($productIds))
                    ->map(fn ($item) => array_merge($item, ['sales_order_id' => $so->id]))
                    ->all()
            );
        };

        // 2 x Draft
        $so = SalesOrder::create(['customer_id' => $customers->random(), 'status' => SalesOrderStatus::Draft, 'created_by' => $staff->id, 'notes' => null, 'created_at' => $now, 'updated_at' => $now]);
        $insert($so, $products->slice(0, 2)->all());

        $so = SalesOrder::create(['customer_id' => $customers->random(), 'status' => SalesOrderStatus::Draft, 'created_by' => $manager->id, 'notes' => null, 'created_at' => $now, 'updated_at' => $now]);
        $insert($so, $products->slice(2, 3)->all());

        // 2 x Confirmed
        $so = SalesOrder::create(['customer_id' => $customers->random(), 'status' => SalesOrderStatus::Confirmed, 'created_by' => $staff->id, 'confirmed_at' => $now, 'notes' => null, 'created_at' => $now, 'updated_at' => $now]);
        $insert($so, $products->slice(5, 2)->all());

        $so = SalesOrder::create(['customer_id' => $customers->random(), 'status' => SalesOrderStatus::Confirmed, 'created_by' => $admin->id, 'confirmed_at' => $now, 'notes' => null, 'created_at' => $now, 'updated_at' => $now]);
        $insert($so, $products->slice(7, 4)->all());

        // 2 x Fulfilled
        $so = SalesOrder::create(['customer_id' => $customers->random(), 'status' => SalesOrderStatus::Fulfilled, 'created_by' => $staff->id, 'confirmed_at' => $now, 'fulfilled_at' => $now, 'notes' => null, 'created_at' => $now, 'updated_at' => $now]);
        $insert($so, $products->slice(11, 3)->all());

        $so = SalesOrder::create(['customer_id' => $customers->random(), 'status' => SalesOrderStatus::Fulfilled, 'created_by' => $manager->id, 'confirmed_at' => $now, 'fulfilled_at' => $now, 'notes' => null, 'created_at' => $now, 'updated_at' => $now]);
        $insert($so, $products->slice(14, 2)->all());

        // 1 x Walk-in
        $so = SalesOrder::create(['customer_id' => $walkin->id, 'status' => SalesOrderStatus::Fulfilled, 'created_by' => $staff->id, 'confirmed_at' => $now, 'fulfilled_at' => $now, 'notes' => 'Cash sale, no invoice required.', 'created_at' => $now, 'updated_at' => $now]);
        $insert($so, $products->slice(16, 2)->all());

        // 1 x Cancelled
        $so = SalesOrder::create(['customer_id' => $customers->random(), 'status' => SalesOrderStatus::Cancelled, 'created_by' => $staff->id, 'notes' => 'Customer cancelled — out of budget.', 'created_at' => $now, 'updated_at' => $now]);
        $insert($so, $products->slice(18, 2)->all());
    }
}
