<?php

namespace App\Policies;

use App\Enums\Position;
use App\Models\SalesOrder;
use App\Models\User;

class SalesOrderPolicy
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
    public function view(User $user, SalesOrder $salesOrder): bool
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
    public function update(User $user, SalesOrder $salesOrder): bool
    {
        if (! in_array($salesOrder->status, ['draft'])) {
            return false;
        }

        if (in_array($user->position, [Position::Admin, Position::Manager])) {
            return true;
        }

        return $salesOrder->created_by === $user->id;
    }

    /**
     * Determine whether the user can confirm the sales order.
     */
    public function confirm(User $user, SalesOrder $salesOrder): bool
    {
        return $salesOrder->status === 'draft'
            && in_array($user->position, [Position::Admin, Position::Manager]);
    }

    /**
     * Determine whether the user can fulfill the sales order.
     */
    public function fulfill(User $user, SalesOrder $salesOrder): bool
    {
        return $salesOrder->status === 'confirmed'
            && in_array($user->position, [Position::Admin, Position::Manager]);
    }

    /**
     * Determine whether the user can cancel the sales order.
     */
    public function cancel(User $user, SalesOrder $salesOrder): bool
    {
        if (! in_array($salesOrder->status, ['draft', 'confirmed'])) {
            return false;
        }

        return in_array($user->position, [Position::Admin, Position::Manager]);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SalesOrder $salesOrder): bool
    {
        if ($salesOrder->status !== 'draft') {
            return false;
        }

        if (in_array($user->position, [Position::Admin, Position::Manager])) {
            return true;
        }

        return $salesOrder->created_by === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SalesOrder $salesOrder): bool
    {
        return $user->position === Position::Admin;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SalesOrder $salesOrder): bool
    {
        return $user->position === Position::Admin;
    }
}
