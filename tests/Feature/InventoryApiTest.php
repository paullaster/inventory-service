<?php

use App\Enums\StockMovementType;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->branch = Branch::create(['name' => 'Main Branch']);
    $this->storeA = Store::create(['name' => 'Store A', 'branch_id' => $this->branch->id]);
    $this->storeB = Store::create(['name' => 'Store B', 'branch_id' => $this->branch->id]);
    $this->product = Product::create(['sku' => 'SKU001', 'name' => 'Product 1']);
    
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->storeManagerA = User::factory()->create([
        'role' => UserRole::StoreManager,
        'branch_id' => $this->branch->id,
        'store_id' => $this->storeA->id
    ]);
    
    // Initial stock
    $service = new InventoryService();
    $service->moveStock($this->product->id, $this->storeA->id, $this->admin, StockMovementType::Procurement, 100);
});

it('can list inventory', function () {
    Sanctum::actingAs($this->admin);

    $response = $this->getJson('/api/inventory');

    $response->assertSuccessful()
        ->assertJsonFragment(['name' => 'Store A'])
        ->assertJsonFragment(['balance' => '100.0000']);
});

it('filters inventory for store managers', function () {
    Sanctum::actingAs($this->storeManagerA);

    $response = $this->getJson('/api/inventory');

    $response->assertSuccessful()
        ->assertJsonFragment(['name' => 'Store A'])
        ->assertJsonMissing(['name' => 'Store B']);
});

it('can record a sale via api', function () {
    Sanctum::actingAs($this->storeManagerA);

    $response = $this->postJson('/api/inventory/movement', [
        'product_id' => $this->product->id,
        'store_id' => $this->storeA->id,
        'type' => StockMovementType::Sale->value,
        'quantity' => -10,
        'description' => 'Test Sale'
    ]);

    $response->assertSuccessful();
    $this->assertDatabaseHas('stock_movements', [
        'store_id' => $this->storeA->id,
        'quantity' => -10,
        'type' => StockMovementType::Sale->value
    ]);
});

it('can transfer stock via api', function () {
    Sanctum::actingAs($this->admin);

    $response = $this->postJson('/api/inventory/transfer', [
        'product_id' => $this->product->id,
        'from_store_id' => $this->storeA->id,
        'to_store_id' => $this->storeB->id,
        'quantity' => 25,
        'description' => 'Test Transfer'
    ]);

    $response->assertSuccessful();
    
    $this->assertDatabaseHas('inventories', [
        'store_id' => $this->storeA->id,
        'balance' => 75
    ]);
    
    $this->assertDatabaseHas('inventories', [
        'store_id' => $this->storeB->id,
        'balance' => 25
    ]);
});

it('prevents unauthorized transfers', function () {
    Sanctum::actingAs($this->storeManagerA); // Store Manager shouldn't transfer to another store freely in this test case logic if not authorized

    $response = $this->postJson('/api/inventory/transfer', [
        'product_id' => $this->product->id,
        'from_store_id' => $this->storeA->id,
        'to_store_id' => $this->storeB->id,
        'quantity' => 25
    ]);

    $response->assertForbidden();
});
