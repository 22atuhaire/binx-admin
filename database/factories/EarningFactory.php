<?php

namespace Database\Factories;

use App\Models\CollectionJob;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Earning>
 */
class EarningFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'collector_id' => User::factory()->collector(),
            'job_id' => CollectionJob::factory(),
            'amount' => fake()->randomFloat(2, 10, 100),
            'description' => 'Payment for waste collection job',
            'earned_at' => fake()->dateTime(),
        ];
    }
}
