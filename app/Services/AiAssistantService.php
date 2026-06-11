<?php

namespace App\Services;

use App\Enums\PurchaseOrderStatus;
use App\Enums\SalesOrderStatus;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AiAssistantService
{
    /**
     * Create a draft purchase order from the confirmed AI payload.
     *
     * @param  array{supplier_id: int|null, items: array<int, array{product_id: int, quantity: int, unit_price: float|null}>, notes?: string}  $payload
     */
    public function createPurchaseOrder(array $payload): PurchaseOrder
    {
        return DB::transaction(function () use ($payload): PurchaseOrder {
            $po = PurchaseOrder::create([
                'supplier_id' => $payload['supplier_id'] ?? null,
                'status' => PurchaseOrderStatus::Draft,
                'created_by' => Auth::id(),
                'notes' => $payload['notes'] ?? null,
            ]);

            foreach ($payload['items'] as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $item['product_id'],
                    'quantity_ordered' => $item['quantity'],
                    'quantity_received' => 0,
                    'unit_cost' => $item['unit_price'] ?? 0,
                ]);
            }

            return $po->load('items.product');
        });
    }

    /**
     * Create a draft sales order from the confirmed AI payload.
     *
     * @param  array{customer_id: int|null, items: array<int, array{product_id: int, quantity: int, unit_price: float|null}>, notes?: string}  $payload
     */
    public function createSalesOrder(array $payload): SalesOrder
    {
        return DB::transaction(function () use ($payload): SalesOrder {
            $so = SalesOrder::create([
                'customer_id' => $payload['customer_id'] ?? null,
                'status' => SalesOrderStatus::Draft,
                'created_by' => Auth::id(),
                'notes' => $payload['notes'] ?? null,
            ]);

            foreach ($payload['items'] as $item) {
                SalesOrderItem::create([
                    'sales_order_id' => $so->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'] ?? 0,
                ]);
            }

            return $so->load('items.product');
        });
    }

    /**
     * Create a new product from the confirmed AI payload.
     *
     * @param  array{name: string, sku: string|null, cost_price: float|null, selling_price: float|null, category_id: int|null, unit: string|null}  $productData
     */
    public function addProduct(array $productData): Product
    {
        return Product::create([
            'name' => $productData['name'],
            'sku' => $productData['sku'] ?? strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $productData['name']), 0, 8)).'-'.rand(100, 999),
            'cost_price' => $productData['cost_price'] ?? 0,
            'selling_price' => $productData['selling_price'] ?? 0,
            'unit_of_measure' => $productData['unit'] ?? 'piece',
            'category_id' => $productData['category_id'] ?? null,
            'reorder_level' => 0,
            'current_stock' => 0,
        ]);
    }

    /**
     * Update an existing product from the confirmed AI payload.
     *
     * @param  array{id: int, name?: string|null, sku?: string|null, cost_price?: float|null, selling_price?: float|null, category_id?: int|null, unit?: string|null}  $productData
     */
    public function editProduct(array $productData): Product
    {
        $product = Product::findOrFail($productData['id']);

        $updates = array_filter([
            'name' => $productData['name'] ?? null,
            'sku' => $productData['sku'] ?? null,
            'cost_price' => $productData['cost_price'] ?? null,
            'selling_price' => $productData['selling_price'] ?? null,
            'unit_of_measure' => $productData['unit'] ?? null,
            'category_id' => $productData['category_id'] ?? null,
        ], fn ($value) => $value !== null);

        $product->update($updates);

        return $product->fresh();
    }

    /**
     * Return current stock levels for the requested product ids.
     *
     * @param  int[]  $productIds
     * @return array<int, array{id: int, name: string, sku: string, current_stock: int|float}>
     */
    public function checkStock(array $productIds): array
    {
        return Product::whereIn('id', $productIds)
            ->get(['id', 'name', 'sku', 'current_stock'])
            ->toArray();
    }
}
