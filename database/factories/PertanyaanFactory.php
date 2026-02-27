<?php

namespace Database\Factories;

use App\Models\Pertanyaan;
use App\Models\SectionQues;
use Illuminate\Database\Eloquent\Factories\Factory;

class PertanyaanFactory extends Factory
{
    protected $model = Pertanyaan::class;

    public function definition(): array
    {
        return [
            'id_sectionques' => SectionQues::factory(),
            'isi_pertanyaan' => fake()->sentence() . '?',
            'status_pertanyaan' => fake()->randomElement(['publish', 'draft', 'hidden']),
        ];
    }
}
