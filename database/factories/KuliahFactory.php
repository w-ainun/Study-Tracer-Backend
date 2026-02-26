<?php

namespace Database\Factories;

use App\Models\Kuliah;
use App\Models\Universitas;
use App\Models\JurusanKuliah;
use App\Models\RiwayatStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class KuliahFactory extends Factory
{
    protected $model = Kuliah::class;

    public function definition(): array
    {
        return [
            'id_universitas' => Universitas::factory(),
            'id_jurusanKuliah' => JurusanKuliah::factory(),
            'jalur_masuk' => fake()->randomElement(['SNBP', 'SNBT', 'Mandiri', 'Beasiswa', 'lainnya']),
            'jenjang' => fake()->randomElement(['D3', 'D4', 'S1', 'S2', 'S3']),
            'id_riwayat' => RiwayatStatus::factory(),
        ];
    }
}
