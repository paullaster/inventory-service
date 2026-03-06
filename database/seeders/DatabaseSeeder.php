<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Branches
        $branchA = Branch::create(['name' => 'Branch A']);
        $branchB = Branch::create(['name' => 'Branch B']);

        // 2. Stores
        $storeA1 = Store::create(['name' => 'Store A-1', 'branch_id' => $branchA->id]);
        $storeB1 = Store::create(['name' => 'Store B-1', 'branch_id' => $branchB->id]);
        $storeB2 = Store::create(['name' => 'Store B-2', 'branch_id' => $branchB->id]);

        // 3. Products (10 SKUs)
        for ($i = 1; $i <= 10; $i++) {
            Product::create([
                'sku' => "SKU-".str_pad($i, 3, '0', STR_PAD_LEFT),
                'name' => "Product $i",
            ]);
        }

        // 4. Users
        // Admin
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@kkwholesalers.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin,
        ]);

        // Branch Manager A
        User::create([
            'name' => 'Branch Manager A',
            'email' => 'branchA@kkwholesalers.com',
            'password' => Hash::make('password'),
            'role' => UserRole::BranchManager,
            'branch_id' => $branchA->id,
        ]);

        // Branch Manager B
        User::create([
            'name' => 'Branch Manager B',
            'email' => 'branchB@kkwholesalers.com',
            'password' => Hash::make('password'),
            'role' => UserRole::BranchManager,
            'branch_id' => $branchB->id,
        ]);

        // Store Managers
        User::create([
            'name' => 'Store Manager A-1',
            'email' => 'storeA1@kkwholesalers.com',
            'password' => Hash::make('password'),
            'role' => UserRole::StoreManager,
            'branch_id' => $branchA->id,
            'store_id' => $storeA1->id,
        ]);

        User::create([
            'name' => 'Store Manager B-1',
            'email' => 'storeB1@kkwholesalers.com',
            'password' => Hash::make('password'),
            'role' => UserRole::StoreManager,
            'branch_id' => $branchB->id,
            'store_id' => $storeB1->id,
        ]);

        User::create([
            'name' => 'Store Manager B-2',
            'email' => 'storeB2@kkwholesalers.com',
            'password' => Hash::make('password'),
            'role' => UserRole::StoreManager,
            'branch_id' => $branchB->id,
            'store_id' => $storeB2->id,
        ]);
    }
}
