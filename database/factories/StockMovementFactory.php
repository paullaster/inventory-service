<?php

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockMovementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'store_id' => Store::factory(),
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement(StockMovementType::cases()),
            'quantity' => $this->faker->randomFloat(4, -50, 50),
            'balance' => $this->faker->randomFloat(4, 0, 500),
            'reference_id' => $this->faker->uuid,
            'description' => $this->faker->sentence,
        ];
    }
}
