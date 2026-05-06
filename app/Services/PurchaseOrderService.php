<?php

namespace App\Services;

use App\Exceptions\InvalidPurchaseOrderStatusException;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    public function __construct(
        private readonly StockMovementService $stockMovementService
    ) {}

    public function create(array $data): PurchaseOrder
    {

        return DB::transaction(function () use ($data) {
            $po = PurchaseOrder::create([
                'supplier_id' => $data['supplier_id'],
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
                'status' => 'pending',
            ]);

            foreach ($data['items'] as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $item['product_id'],
                    'quantity_ordered' => $item['quantity_ordered'],
                    'quantity_received' => 0,
                    'unit_cost' => $item['unit_cost'],
                ]);
            }

            return $po;
        });

    }

    public function approve(PurchaseOrder $po): PurchaseOrder
    {
        if ($po->status !== 'pending') {
            throw new InvalidPurchaseOrderStatusException(
                'Only pending purchase orders can be approved.'
            );
        }

        $po->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return $po;
    }

    public function submit(PurchaseOrder $po): PurchaseOrder
    {
        if ($po->status !== 'approved') {
            throw new InvalidPurchaseOrderStatusException(
                'Only approved purchase orders can be submitted.'
            );
        }

        $po->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        return $po;
    }

    public function receive(PurchaseOrder $po, array $receivedItems): PurchaseOrder
    {
        if (! in_array($po->status, ['submitted', 'partially_received'])) {
            throw new InvalidPurchaseOrderStatusException(
                'Only submitted or partially received purchase orders can be received.'
            );
        }

        DB::transaction(function () use ($po, $receivedItems) {
            foreach ($receivedItems as $receivedItem) {
                $item = PurchaseOrderItem::where('purchase_order_id', $po->id)
                    ->where('product_id', $receivedItem['product_id'])
                    ->firstOrFail();

                $quantityToReceive = $receivedItem['quantity_received'];
                $remaining = $item->quantity_ordered - $item->quantity_received;

                if ($quantityToReceive > $remaining) {
                    throw new \InvalidArgumentException(
                        "Received quantity exceeds remaining ordered quantity for product ID {$item->product_id}."
                    );
                }

                if ($quantityToReceive <= 0) {
                    continue;
                }

                $item->increment('quantity_received', $quantityToReceive);

                $this->stockMovementService->stockIn(
                    $item->product,
                    $quantityToReceive,
                    $item->unit_cost,
                    'purchase_order',
                    $po->id,
                );
            }

            // Reload items to get fresh quantity_received values
            $po->load('items');

            $newStatus = $po->isFullyReceived() ? 'received' : 'partially_received';

            $po->update([
                'status' => $newStatus,
                'received_at' => $po->isFullyReceived() ? now() : null,
            ]);
        });

        return $po->fresh();

    }

    public function cancel(PurchaseOrder $po): PurchaseOrder
    {
        if (in_array($po->status, ['received', 'closed', 'cancelled'])) {
            throw new InvalidPurchaseOrderStatusException(
                'This purchase order cannot be cancelled.'
            );
        }

        $po->update(['status' => 'cancelled']);

        return $po;
    }

    public function close(PurchaseOrder $po): PurchaseOrder
    {
        if ($po->status !== 'partially_received') {
            throw new InvalidPurchaseOrderStatusException(
                'Only partially received purchase orders can be closed.'
            );
        }

        $po->update(['status' => 'closed']);

        return $po;
    }
}
