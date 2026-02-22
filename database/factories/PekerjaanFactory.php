<?php

namespace Database\Factories;

use App\Models\Pekerjaan;
use App\Models\Perusahaan;
use App\Models\RiwayatStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class PekerjaanFactory extends Factory
{
    protected $model = Pekerjaan::class;

    public function definition(): array
    {
        return [
            'posisi' => fake()->jobTitle(),
            'id_perusahaan' => Perusahaan::factory(),
            'id_riwayat' => RiwayatStatus::factory(),
        ];
    }
}
