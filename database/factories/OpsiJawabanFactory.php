<?php

namespace Database\Factories;

use App\Models\OpsiJawaban;
use App\Models\PertanyaanKuesioner;
use Illuminate\Database\Eloquent\Factories\Factory;

class OpsiJawabanFactory extends Factory
{
    protected $model = OpsiJawaban::class;

    public function definition(): array
    {
        return [
            'id_pertanyaan' => PertanyaanKuesioner::factory(),
            'opsi' => fake()->sentence(3),
        ];
    }
}
