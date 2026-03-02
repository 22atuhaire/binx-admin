<?php

namespace Database\Factories;

use App\Models\Job;
use App\Models\WastePost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Job>
 */
class JobFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'waste_post_id' => WastePost::factory(),
            'collector_id' => User::factory()->collector(),
            'status' => fake()->randomElement(['pending', 'in_progress', 'completed']),
            'assigned_at' => now(),
            'completed_at' => fake()->optional()->dateTime(),
        ];
    }

    /**
     * Create a pending job.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'completed_at' => null,
        ]);
    }

    /**
     * Create a completed job.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}
