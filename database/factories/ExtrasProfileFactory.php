<?php

namespace Database\Factories;

use App\Models\ExtrasProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExtrasProfile>
 */
class ExtrasProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'extras']),
            'alias' => fake()->name(),
            'nama_asli' => fake()->name(),
            'usia' => fake()->numberBetween(20, 45),
            'gender' => fake()->randomElement(['Pria', 'Wanita']),
            'tinggi_badan' => fake()->numberBetween(155, 185),
            'ukuran_baju' => fake()->randomElement(['S', 'M', 'L', 'XL']),
            'warna_kulit' => fake()->randomElement(['Sawo Matang', 'Kuning Langsat', 'Putih']),
            'pengalaman' => fake()->sentence(),
            'bahasa' => 'Indonesia',
            'rate_card' => fake()->numberBetween(150000, 500000),
            'status' => 'aktif',
            'cancel_count' => 0,
        ];
    }
}
