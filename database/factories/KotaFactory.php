<?php

namespace Database\Factories;

use App\Models\Kota;
use App\Models\Provinsi;
use Illuminate\Database\Eloquent\Factories\Factory;

class KotaFactory extends Factory
{
    protected $model = Kota::class;

    public function definition(): array
    {
        return [
            'nama_kota' => fake()->city(),
            'id_provinsi' => Provinsi::factory(),
        ];
    }
}
