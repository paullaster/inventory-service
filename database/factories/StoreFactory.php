<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

class StoreFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->streetName . ' Store',
            'branch_id' => Branch::factory(),
        ];
    }
}
