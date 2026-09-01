<?php

namespace Database\Factories;

use App\Models\CastingProject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CastingProject>
 */
class CastingProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'admin_id' => User::factory()->state(['role' => 'admin_default']),
            'nama_produksi' => fake()->sentence(3),
            'client_ph' => fake()->company(),
            'deadline' => now()->addDays(7),
            'kuota' => fake()->numberBetween(5, 20),
            'is_urgent' => false,
            'status' => 'dibuka',
        ];
    }
}
