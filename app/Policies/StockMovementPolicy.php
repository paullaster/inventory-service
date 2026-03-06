<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Store;

class StockMovementPolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can create stock movements
     */
    public function create(User $user, int $storeId): bool
    {
        if ($user->role === UserRole::BranchManager) {
            // Check if store belongs to user's branch
            $store = Store::find($storeId);
            return $store && $store->branch_id === $user->branch_id;
        }

        if ($user->role === UserRole::StoreManager) {
            return $user->store_id === $storeId;
        }

        return false;
    }

    /**
     * Determine whether the user can transfer stock.
     */
    public function transfer(User $user, int $fromStoreId, int $toStoreId): bool
    {
        if ($user->role === UserRole::BranchManager) {
            $fromStore = Store::find($fromStoreId);
            $toStore = Store::find($toStoreId);

            return $fromStore && $toStore &&
                   $fromStore->branch_id === $user->branch_id &&
                   $toStore->branch_id === $user->branch_id;
        }

        return false;
    }
}
