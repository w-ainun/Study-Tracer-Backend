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
        $kota = Kota::inRandomOrder()->first();
        $kotaId = $kota ? $kota->id_kota : Kota::factory();
        $kotaName = $kota ? $kota->nama_kota : fake()->city();

        return [
            'nama_perusahaan' => fake()->company(),
            'id_kota' => $kotaId,
            'jalan' => fake()->streetAddress() . ', ' . $kotaName,
        ];
    }
}
