<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuctionFactory extends Factory
{
    public function definition(): array
    {
        $startingPrice = fake()->numberBetween(100000, 1000000);

        return [
            'seller_id' => User::factory(),
            'title' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'starting_price' => $startingPrice,
            'bid_increment' => fake()->randomElement([10000, 25000, 50000]),
            'current_price' => $startingPrice,
            'buy_now_price' => null,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
            'status' => 'active',
        ];
    }

    public function scheduled(): static
    {
        return $this->state(fn () => [
            'starts_at' => now()->addHours(2),
            'ends_at' => now()->addDay(),
            'status' => 'scheduled',
        ]);
    }

    public function ended(): static
    {
        return $this->state(fn () => [
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subHour(),
            'status' => 'ended',
        ]);
    }
}