<?php

namespace App\Policies;

use App\Enums\Position;
use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Customer $customer): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return in_array($user->position, [Position::Admin, Position::Manager]);
    }

    public function update(User $user, Customer $customer): bool
    {
        if ($customer->is_system) {
            return false;
        }

        return in_array($user->position, [Position::Admin, Position::Manager]);
    }

    public function delete(User $user, Customer $customer): bool
    {
        if ($customer->is_system) {
            return false;
        }

        return $user->position === Position::Admin;
    }

    public function restore(User $user, Customer $customer): bool
    {
        return $user->position === Position::Admin;
    }

    public function forceDelete(User $user, Customer $customer): bool
    {
        if ($customer->is_system) {
            return false;
        }

        return $user->position === Position::Admin;
    }
}
