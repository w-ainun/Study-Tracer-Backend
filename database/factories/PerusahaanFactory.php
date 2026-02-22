<?php

namespace Database\Factories;

use App\Models\Perusahaan;
use App\Models\Kota;
use Illuminate\Database\Eloquent\Factories\Factory;

class PerusahaanFactory extends Factory
{
    protected $model = Perusahaan::class;

    public function definition(): array
    {
        return [
            'nama_perusahaan' => fake()->company(),
            'id_kota' => Kota::factory(),
            'jalan' => fake()->streetAddress(),
        ];
    }
}
