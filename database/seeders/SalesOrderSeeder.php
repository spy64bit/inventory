<?php

namespace Database\Seeders;

use App\Enums\Position;
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
        $productIds = Product::pluck('id');

        $itemState = fn () => ['product_id' => $productIds->random()];

        // 2 x Draft — staff creating orders, not yet confirmed
        SalesOrder::factory()
            ->has(SalesOrderItem::factory()->count(2)->state($itemState), 'items')
            ->create(['customer_id' => $customers->random(), 'created_by' => $staff->id]);

        SalesOrder::factory()
            ->has(SalesOrderItem::factory()->count(3)->state($itemState), 'items')
            ->create(['customer_id' => $customers->random(), 'created_by' => $manager->id]);

        // 2 x Confirmed — approved, awaiting fulfillment
        SalesOrder::factory()
            ->confirmed()
            ->has(SalesOrderItem::factory()->count(2)->state($itemState), 'items')
            ->create(['customer_id' => $customers->random(), 'created_by' => $staff->id]);

        SalesOrder::factory()
            ->confirmed()
            ->has(SalesOrderItem::factory()->count(4)->state($itemState), 'items')
            ->create(['customer_id' => $customers->random(), 'created_by' => $admin->id]);

        // 2 x Fulfilled — completed orders
        SalesOrder::factory()
            ->fulfilled()
            ->has(SalesOrderItem::factory()->count(3)->state($itemState), 'items')
            ->create(['customer_id' => $customers->random(), 'created_by' => $staff->id]);

        SalesOrder::factory()
            ->fulfilled()
            ->has(SalesOrderItem::factory()->count(2)->state($itemState), 'items')
            ->create(['customer_id' => $customers->random(), 'created_by' => $manager->id]);

        // 1 x Walk-in cash sale — fulfilled immediately
        SalesOrder::factory()
            ->fulfilled()
            ->has(SalesOrderItem::factory()->count(2)->state($itemState), 'items')
            ->create([
                'customer_id' => $walkin->id,
                'created_by' => $staff->id,
                'notes' => 'Cash sale, no invoice required.',
            ]);

        // 1 x Cancelled
        SalesOrder::factory()
            ->cancelled()
            ->has(SalesOrderItem::factory()->count(2)->state($itemState), 'items')
            ->create([
                'customer_id' => $customers->random(),
                'created_by' => $staff->id,
                'notes' => 'Customer cancelled — out of budget.',
            ]);
    }
}
