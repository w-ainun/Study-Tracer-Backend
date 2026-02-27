<?php

namespace Database\Factories;

use App\Models\SectionQues;
use App\Models\Kuesioner;
use Illuminate\Database\Eloquent\Factories\Factory;

class SectionQuesFactory extends Factory
{
    protected $model = SectionQues::class;

    public function definition(): array
    {
        return [
            'id_kuesioner' => Kuesioner::factory(),
            'judul_pertanyaan' => fake()->sentence(3),
        ];
    }
}
