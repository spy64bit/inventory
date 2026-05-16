<?php

namespace App\Services;

use App\Enums\PurchaseOrderStatus;
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
                'status' => PurchaseOrderStatus::Draft,
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

    public function update(PurchaseOrder $purchaseOrder, array $data): PurchaseOrder
    {
        if (! in_array($purchaseOrder->status, [PurchaseOrderStatus::Draft, PurchaseOrderStatus::Approved])) {
            throw new InvalidPurchaseOrderStatusException(
                'Only draft or approved purchase orders can be updated.'
            );
        }

        DB::transaction(function () use ($purchaseOrder, $data) {
            $purchaseOrder->update([
                'supplier_id' => $data['supplier_id'],
                'notes' => $data['notes'] ?? null,
            ]);

            // Delete removed items (only if nothing received yet)
            $submittedIds = collect($data['items'])
                ->pluck('id')
                ->filter()
                ->values();

            $purchaseOrder->items()
                ->whereNotIn('id', $submittedIds)
                ->where('quantity_received', 0)
                ->delete();

            foreach ($data['items'] as $itemData) {
                if (isset($itemData['id'])) {
                    $item = PurchaseOrderItem::where('purchase_order_id', $purchaseOrder->id)
                        ->where('id', $itemData['id'])
                        ->firstOrFail();

                    if ($item->quantity_received > 0 && (
                        $itemData['quantity_ordered'] < $item->quantity_received ||
                        $itemData['unit_cost'] != $item->unit_cost
                    )) {
                        throw new \InvalidArgumentException(
                            'Cannot reduce quantity ordered below quantity received or change unit cost for items already received.'
                        );
                    }

                    $item->update([
                        'product_id' => $itemData['product_id'],
                        'quantity_ordered' => $itemData['quantity_ordered'],
                        'unit_cost' => $itemData['unit_cost'],
                    ]);
                } else {
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $purchaseOrder->id,
                        'product_id' => $itemData['product_id'],
                        'quantity_ordered' => $itemData['quantity_ordered'],
                        'quantity_received' => 0,
                        'unit_cost' => $itemData['unit_cost'],
                    ]);
                }
            }
        });

        return $purchaseOrder->fresh();
    }

    public function approve(PurchaseOrder $po): PurchaseOrder
    {
        if ($po->status !== PurchaseOrderStatus::Draft) {
            throw new InvalidPurchaseOrderStatusException(
                'Only draft purchase orders can be approved.'
            );
        }

        $po->update([
            'status' => PurchaseOrderStatus::Approved,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return $po;
    }

    public function dispatch(PurchaseOrder $po): PurchaseOrder
    {
        if ($po->status !== PurchaseOrderStatus::Approved) {
            throw new InvalidPurchaseOrderStatusException(
                'Only approved purchase orders can be dispatched.'
            );
        }

        $po->update([
            'status' => PurchaseOrderStatus::Dispatched,
            'dispatched_at' => now(),
        ]);

        return $po;
    }

    public function receive(PurchaseOrder $po, array $receivedItems): PurchaseOrder
    {
        if (! in_array($po->status, [PurchaseOrderStatus::Dispatched, PurchaseOrderStatus::PartiallyReceived])) {
            throw new InvalidPurchaseOrderStatusException(
                'Only dispatched or partially received purchase orders can be received.'
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

            $newStatus = $po->isFullyReceived() ? PurchaseOrderStatus::Received : PurchaseOrderStatus::PartiallyReceived;

            $po->update([
                'status' => $newStatus,
                'received_at' => $po->isFullyReceived() ? now() : null,
            ]);
        });

        return $po->fresh();

    }

    public function cancel(PurchaseOrder $po): PurchaseOrder
    {
        $nonCancellableStatuses = [
            PurchaseOrderStatus::PartiallyReceived,
            PurchaseOrderStatus::Received,
            PurchaseOrderStatus::Closed,
            PurchaseOrderStatus::Cancelled,
        ];

        if (in_array($po->status, $nonCancellableStatuses)) {
            throw new InvalidPurchaseOrderStatusException(
                'This purchase order cannot be cancelled.'
            );
        }

        $po->update(['status' => PurchaseOrderStatus::Cancelled]);

        return $po;
    }

    public function close(PurchaseOrder $po): PurchaseOrder
    {
        if ($po->status !== PurchaseOrderStatus::PartiallyReceived) {
            throw new InvalidPurchaseOrderStatusException(
                'Only partially received purchase orders can be closed.'
            );
        }

        $po->update(['status' => PurchaseOrderStatus::Closed]);

        return $po;
    }
}
