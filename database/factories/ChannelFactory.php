<?php

namespace Database\Factories;

use App\Models\Channel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Channel>
 */
class ChannelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->boolean(70) ? fake()->sentence() : null,
            'visibility' => 'public',
            'created_by' => User::factory(),
        ];
    }

    public function private(): static
    {
        return $this->state(fn () => ['visibility' => 'private']);
    }
}
