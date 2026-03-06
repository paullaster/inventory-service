<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Inventory;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    /**
     * Move stock for a specific store.
     */
    public function moveStock(
        int $productId,
        int $storeId,
        User $user,
        StockMovementType $type,
        float $quantity, // Positive for increase, negative for decrease
        ?string $referenceId = null,
        ?string $description = null
    ): Inventory {
        return DB::transaction(function () use ($productId, $storeId, $user, $type, $quantity, $referenceId, $description) {
            // 1. Get/Create Inventory record with lock
            $inventory = Inventory::where('product_id', $productId)
                ->where('store_id', $storeId)
                ->lockForUpdate()
                ->first();

            if (! $inventory) {
                // If it doesn't exist, we create it
                $inventory = Inventory::create([
                    'product_id' => $productId,
                    'store_id' => $storeId,
                    'balance' => 0,
                ]);
            }

            // 2. Validate sufficient stock for negative movements (sales/transfers out)
            if ($quantity < 0 && $inventory->balance < abs($quantity)) {
                throw new InvalidArgumentException("Insufficient stock in store #{$storeId} for product #{$productId}.");
            }

            // 3. Update balance
            $inventory->balance += $quantity;
            $inventory->save();

            // 4. Record movement for audit trail
            StockMovement::create([
                'product_id' => $productId,
                'store_id' => $storeId,
                'user_id' => $user->id,
                'type' => $type,
                'quantity' => $quantity,
                'balance' => $inventory->balance,
                'reference_id' => $referenceId,
                'description' => $description,
            ]);

            return $inventory;
        });
    }

    /**
     * Transfer stock between two stores.
     */
    public function transferStock(
        int $productId,
        int $fromStoreId,
        int $toStoreId,
        User $user,
        float $quantity,
        ?string $referenceId = null,
        ?string $description = null
    ): void {
        if ($quantity <= 0) {
            throw new InvalidArgumentException("Transfer quantity must be positive.");
        }

        DB::transaction(function () use ($productId, $fromStoreId, $toStoreId, $user, $quantity, $referenceId, $description) {
            // We move out from source
            $this->moveStock(
                $productId,
                $fromStoreId,
                $user,
                StockMovementType::TransferOut,
                -$quantity,
                $referenceId,
                $description ?? "Transfer to store #{$toStoreId}"
            );

            // We move into destination
            $this->moveStock(
                $productId,
                $toStoreId,
                $user,
                StockMovementType::TransferIn,
                $quantity,
                $referenceId,
                $description ?? "Transfer from store #{$fromStoreId}"
            );
        });
    }
}
