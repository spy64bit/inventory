<?php

namespace App\Policies;

use App\Enums\Position;
use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        $editableStatuses = [
            PurchaseOrderStatus::Draft,
            PurchaseOrderStatus::Approved,
        ];

        if (! in_array($purchaseOrder->status, $editableStatuses)) {
            return false;
        }

        if ($user->position === Position::Staff) {
            return $purchaseOrder->status === PurchaseOrderStatus::Draft;
        }

        return in_array($user->position, [Position::Admin, Position::Manager]);
    }

    public function approve(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $purchaseOrder->status === PurchaseOrderStatus::Draft
            && in_array($user->position, [Position::Admin, Position::Manager]) &&
            $user->id !== $purchaseOrder->created_by;
    }

    public function dispatch(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $purchaseOrder->status === PurchaseOrderStatus::Approved
            && in_array($user->position, [Position::Admin, Position::Manager]);
    }

    public function receive(User $user, PurchaseOrder $purchaseOrder): bool
    {
        $status = [
            PurchaseOrderStatus::Dispatched,
            PurchaseOrderStatus::PartiallyReceived,
        ];

        return in_array($purchaseOrder->status, $status);
    }

    public function close(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $purchaseOrder->status === PurchaseOrderStatus::PartiallyReceived
            && in_array($user->position, [Position::Admin, Position::Manager]);
    }

    public function cancel(User $user, PurchaseOrder $purchaseOrder): bool
    {
        $status = [
            PurchaseOrderStatus::Draft,
            PurchaseOrderStatus::Approved,
            PurchaseOrderStatus::Dispatched,
        ];

        return in_array($purchaseOrder->status, $status)
            && in_array($user->position, [Position::Admin, Position::Manager]);
    }

    // public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    // {
    //     return $user->position === Position::Admin && $purchaseOrder->status === PurchaseOrderStatus::Draft;
    // }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return false;
    }
}
