<?php

namespace Database\Factories;

use App\Models\PertanyaanKuesioner;
use App\Models\Kuesioner;
use Illuminate\Database\Eloquent\Factories\Factory;

class PertanyaanKuesionerFactory extends Factory
{
    protected $model = PertanyaanKuesioner::class;

    public function definition(): array
    {
        return [
            'id_kuesioner' => Kuesioner::factory(),
            'pertanyaan' => fake()->sentence() . '?',
        ];
    }
}
