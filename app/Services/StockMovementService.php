<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovements;
use Illuminate\Support\Facades\DB;

class StockMovementService
{
    public function stockIn(Product $product, int $quantity, string $remarks = ''): void
    {

        DB::transaction(function () use ($product, $quantity, $remarks) {

            $product = Product::lockForUpdate()->findOrFail($product->id);

            // Create a new stock movement record for stock in
            StockMovements::create([
                'product_id' => $product->id,
                'type' => 'in',
                'quantity' => $quantity,
                'user_id' => auth()->guard()->id(),
                'remarks' => $remarks,
            ]);

            // Update the product's stock quantity
            $product->increment('stock_quantity', $quantity);
        });

    }

    public function stockOut(Product $product, int $quantity, string $remarks = ''): void
    {
        DB::transaction(function () use ($product, $quantity, $remarks) {

            $product = Product::lockForUpdate()->findOrFail($product->id);

            if ($product->stock_quantity < $quantity) {
                throw new \Exception('Insufficient stock for product: '.$product->name);
            }

            // Create a new stock movement record for stock out
            StockMovements::create([
                'product_id' => $product->id,
                'type' => 'out',
                'quantity' => $quantity,
                'user_id' => auth()->guard()->id(),
                'remarks' => $remarks,
            ]);

            // Update the product's stock quantity
            $product->decrement('stock_quantity', $quantity);

        });

    }
}
