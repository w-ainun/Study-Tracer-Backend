<?php

namespace Database\Factories;

use App\Models\JawabanKuesioner;
use App\Models\PertanyaanKuesioner;
use App\Models\User;
use App\Models\OpsiJawaban;
use Illuminate\Database\Eloquent\Factories\Factory;

class JawabanKuesionerFactory extends Factory
{
    protected $model = JawabanKuesioner::class;

    public function definition(): array
    {
        return [
            'id_pertanyaan' => PertanyaanKuesioner::factory(),
            'id_user' => User::factory(),
            'id_opsiJawaban' => OpsiJawaban::factory(),
            'jawaban' => fake()->optional()->sentence(),
            'status' => fake()->randomElement(['Selesai', 'Belum Selesai']),
        ];
    }
}
