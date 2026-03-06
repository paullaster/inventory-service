<?php

use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use App\Enums\UserRole;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->branchA = Branch::create(['name' => 'Branch A']);
    $this->branchB = Branch::create(['name' => 'Branch B']);

    $this->storeA1 = Store::create(['name' => 'Store A-1', 'branch_id' => $this->branchA->id]);
    $this->storeB1 = Store::create(['name' => 'Store B-1', 'branch_id' => $this->branchB->id]);
});

it('allows admin to create movements anywhere', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    expect(Gate::forUser($admin)->allows('create', [\App\Models\StockMovement::class, $this->storeA1->id]))->toBeTrue();
    expect(Gate::forUser($admin)->allows('create', [\App\Models\StockMovement::class, $this->storeB1->id]))->toBeTrue();
});

it('allows branch manager to create movements in their stores only', function () {
    $managerA = User::factory()->create([
        'role' => UserRole::BranchManager,
        'branch_id' => $this->branchA->id,
    ]);

    expect(Gate::forUser($managerA)->allows('create', [\App\Models\StockMovement::class, $this->storeA1->id]))->toBeTrue();
    expect(Gate::forUser($managerA)->allows('create', [\App\Models\StockMovement::class, $this->storeB1->id]))->toBeFalse();
});

it('allows store manager to create movements in their store only', function () {
    $managerA1 = User::factory()->create([
        'role' => UserRole::StoreManager,
        'branch_id' => $this->branchA->id,
        'store_id' => $this->storeA1->id,
    ]);

    expect(Gate::forUser($managerA1)->allows('create', [\App\Models\StockMovement::class, $this->storeA1->id]))->toBeTrue();
    expect(Gate::forUser($managerA1)->allows('create', [\App\Models\StockMovement::class, $this->storeB1->id]))->toBeFalse();
});

it('allows branch manager to transfer between their stores', function () {
    $storeA2 = Store::create(['name' => 'Store A-2', 'branch_id' => $this->branchA->id]);
    $managerA = User::factory()->create([
        'role' => UserRole::BranchManager,
        'branch_id' => $this->branchA->id,
    ]);

    // Same branch
    expect(Gate::forUser($managerA)->allows('transfer', [\App\Models\StockMovement::class, $this->storeA1->id, $storeA2->id]))->toBeTrue();

    // Cross branch
    expect(Gate::forUser($managerA)->allows('transfer', [\App\Models\StockMovement::class, $this->storeA1->id, $this->storeB1->id]))->toBeFalse();
});
