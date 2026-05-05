<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockMovementService
{
    public function stockIn(
        Product $product,
        float $quantity,
        float $unitCost,
        string $referenceType = 'adjustment',
        ?int $referenceId = null,
        string $remarks = ''): void
    {

        DB::transaction(function () use ($product, $quantity, $unitCost, $referenceType, $referenceId, $remarks) {

            $product = Product::lockForUpdate()->findOrFail($product->id);

            // Create a new stock movement record for stock in
            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'in',
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'user_id' => auth()->guard()->id(),
                'remarks' => $remarks,
            ]);

            // Update the product's stock quantity
            $product->increment('current_stock', $quantity);
        });

    }

    public function stockOut(
        Product $product,
        float $quantity,
        string $referenceType = 'adjustment',
        ?int $referenceId = null,
        string $remarks = ''
    ): void {
        DB::transaction(function () use ($product, $quantity, $referenceType, $referenceId, $remarks) {

            $product = Product::lockForUpdate()->findOrFail($product->id);

            if ($product->current_stock < $quantity) {
                throw new InsufficientStockException($product);
            }

            // Create a new stock movement record for stock out
            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'out',
                'quantity' => $quantity,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'user_id' => auth()->guard()->id(),
                'remarks' => $remarks,
            ]);

            // Update the product's stock quantity
            $product->decrement('current_stock', $quantity);

        });

    }
}
