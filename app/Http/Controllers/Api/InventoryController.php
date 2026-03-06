<?php

namespace App\Http\Controllers\Api;

use App\Enums\StockMovementType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public function __construct(protected InventoryService $service) {}

    /**
     * Get inventory summary (current stock levels).
     */
    public function index(Request $request)
    {
        $query = Inventory::with(['product', 'store.branch']);

        // Filter by user role/permissions
        $user = $request->user();
        if ($user->role === UserRole::BranchManager) {
            $query->whereHas('store', fn($q) => $q->where('branch_id', $user->branch_id));
        } elseif ($user->role === UserRole::StoreManager) {
            $query->where('store_id', $user->store_id);
        }

        return $query->paginate(20);
    }

    /**
     * Get audit trail (stock movements).
     */
    public function history(Request $request)
    {
        $query = StockMovement::with(['product', 'store', 'user'])->latest();

        // Filter by user role/permissions
        $user = $request->user();
        if ($user->role === UserRole::BranchManager) {
            $query->whereHas('store', fn($q) => $q->where('branch_id', $user->branch_id));
        } elseif ($user->role === UserRole::StoreManager) {
            $query->where('store_id', $user->store_id);
        }
        
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        return $query->paginate(20);
    }

    /**
     * Record a sale or adjustment (single store movement).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'store_id' => 'required|exists:stores,id',
            'type' => ['required', Rule::enum(StockMovementType::class)],
            'quantity' => 'required|numeric',
            'description' => 'nullable|string',
            'reference_id' => 'nullable|string',
        ]);

        // Authorization check
        Gate::authorize('create', [StockMovement::class, $validated['store_id']]);

        // Execute movement
        $inventory = $this->service->moveStock(
            $validated['product_id'],
            $validated['store_id'],
            $request->user(),
            StockMovementType::from($validated['type']),
            $validated['quantity'],
            $validated['reference_id'] ?? null,
            $validated['description'] ?? null
        );

        return response()->json([
            'message' => 'Stock movement recorded successfully.',
            'inventory' => $inventory->load(['product', 'store'])
        ]);
    }

    /**
     * Transfer stock between stores.
     */
    public function transfer(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'from_store_id' => 'required|exists:stores,id',
            'to_store_id' => 'required|exists:stores,id|different:from_store_id',
            'quantity' => 'required|numeric|min:0.0001',
            'description' => 'nullable|string',
            'reference_id' => 'nullable|string',
        ]);

        // Authorization check
        Gate::authorize('transfer', [
            StockMovement::class, 
            $validated['from_store_id'], 
            $validated['to_store_id']
        ]);

        // Execute transfer
        $this->service->transferStock(
            $validated['product_id'],
            $validated['from_store_id'],
            $validated['to_store_id'],
            $request->user(),
            $validated['quantity'],
            $validated['reference_id'] ?? null,
            $validated['description'] ?? null
        );

        return response()->json([
            'message' => 'Stock transfer completed successfully.'
        ]);
    }
}
