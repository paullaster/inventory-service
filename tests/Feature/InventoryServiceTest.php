<?php

use App\Enums\StockMovementType;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new InventoryService();
    $this->branch = Branch::create(['name' => 'Main Branch']);
    $this->storeA = Store::create(['name' => 'Store A', 'branch_id' => $this->branch->id]);
    $this->storeB = Store::create(['name' => 'Store B', 'branch_id' => $this->branch->id]);
    $this->product = Product::create(['sku' => 'SKU001', 'name' => 'Product 1']);
    $this->admin = User::factory()->create([
        'role' => UserRole::Admin,
    ]);
});

it('can add stock through procurement', function () {
    $inventory = $this->service->moveStock(
        $this->product->id,
        $this->storeA->id,
        $this->admin,
        StockMovementType::Procurement,
        100
    );

    expect($inventory->balance)->toEqual(100);
    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $this->product->id,
        'store_id' => $this->storeA->id,
        'type' => StockMovementType::Procurement->value,
        'quantity' => 100,
        'balance' => 100,
    ]);
});

it('can decrease stock through sales', function () {
    // Initial stock
    $this->service->moveStock(
        $this->product->id,
        $this->storeA->id,
        $this->admin,
        StockMovementType::Procurement,
        100
    );

    $inventory = $this->service->moveStock(
        $this->product->id,
        $this->storeA->id,
        $this->admin,
        StockMovementType::Sale,
        -30
    );

    expect($inventory->balance)->toEqual(70);
    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $this->product->id,
        'store_id' => $this->storeA->id,
        'type' => StockMovementType::Sale->value,
        'quantity' => -30,
        'balance' => 70,
    ]);
});

it('prevents sales when stock is insufficient', function () {
    $this->service->moveStock(
        $this->product->id,
        $this->storeA->id,
        $this->admin,
        StockMovementType::Procurement,
        10
    );

    $this->service->moveStock(
        $this->product->id,
        $this->storeA->id,
        $this->admin,
        StockMovementType::Sale,
        -20
    );
})->throws(InvalidArgumentException::class, 'Insufficient stock');

it('can transfer stock between stores', function () {
    // Initial stock in Store A
    $this->service->moveStock(
        $this->product->id,
        $this->storeA->id,
        $this->admin,
        StockMovementType::Procurement,
        100
    );

    $this->service->transferStock(
        $this->product->id,
        $this->storeA->id,
        $this->storeB->id,
        $this->admin,
        40
    );

    expect(\App\Models\Inventory::where('store_id', $this->storeA->id)->first()->balance)->toEqual(60);
    expect(\App\Models\Inventory::where('store_id', $this->storeB->id)->first()->balance)->toEqual(40);

    $this->assertDatabaseHas('stock_movements', [
        'store_id' => $this->storeA->id,
        'type' => StockMovementType::TransferOut->value,
        'quantity' => -40,
        'balance' => 60,
    ]);

    $this->assertDatabaseHas('stock_movements', [
        'store_id' => $this->storeB->id,
        'type' => StockMovementType::TransferIn->value,
        'quantity' => 40,
        'balance' => 40,
    ]);
});
