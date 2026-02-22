<?php

namespace Database\Factories;

use App\Models\Kuesioner;
use Illuminate\Database\Eloquent\Factories\Factory;

class KuesionerFactory extends Factory
{
    protected $model = Kuesioner::class;

    public function definition(): array
    {
        return [
            'judul_kuesioner' => fake()->sentence(4),
            'deskripsi_kuesioner' => fake()->sentence(10),
            'status_kuesioner' => fake()->randomElement(['draft', 'publish', 'close']),
            'tanggal_publikasi' => fake()->optional()->date(),
        ];
    }
}
